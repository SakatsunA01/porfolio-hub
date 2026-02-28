<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $tenantId = $this->resolveTenantId();

        $productsQuery = Product::query()
            ->with(['ingredients', 'extras'])
            ->orderBy('name');
        $this->scopeQueryByTenant($productsQuery, $tenantId);

        $products = $productsQuery
            ->get()
            ->map(function (Product $product) {
                $product->setAttribute('image_url', $product->getFirstMediaUrl('images') ?: $product->image_url);
                return $product;
            });

        return response()->json($products->values());
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($product->tenant_id)) {
            return response()->json(['message' => 'Producto fuera del tenant actual.'], 403);
        }

        $product->load([
            'ingredients' => fn ($query) => $query->where('ingredients.is_active', true),
            'extras' => fn ($query) => $query->where('is_active', true),
        ]);

        $selectedExtraIds = $this->parseIdList($request->query('extras', []));
        $excludedIngredientIds = $this->parseIdList($request->query('excluded_ingredients', []));

        $selectedExtras = $product->extras->whereIn('id', $selectedExtraIds)->values();

        $removableIncludedIngredients = $product->ingredients
            ->filter(fn ($ingredient) => (bool) $ingredient->pivot?->is_default && (bool) $ingredient->pivot?->is_removable)
            ->pluck('id');

        $effectiveExcluded = $removableIncludedIngredients
            ->intersect($excludedIngredientIds)
            ->values();

        $extrasTotal = (float) $selectedExtras->sum('additional_price');
        $basePrice = (float) $product->base_price;

        return response()->json([
            'product' => tap($product, function (Product $model) {
                $model->setAttribute('image_url', $model->getFirstMediaUrl('images') ?: $model->image_url);
            }),
            'customization' => [
                'included_ingredients' => $product->ingredients
                    ->where('pivot.is_default', true)
                    ->values(),
                'removable_ingredients' => $product->ingredients
                    ->filter(fn ($ingredient) => (bool) $ingredient->pivot?->is_removable)
                    ->values(),
                'excluded_ingredients' => $product->ingredients
                    ->whereIn('id', $effectiveExcluded)
                    ->values(),
            ],
            'pricing' => [
                'base_price' => $basePrice,
                'extras_total' => $extrasTotal,
                'final_price' => round($basePrice + $extrasTotal, 2),
            ],
            'selected_extras' => $selectedExtras,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'prep_min' => ['sometimes', 'integer', 'min:1'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'min_stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
            'ingredient_ids' => ['sometimes', 'array'],
            'ingredient_ids.*' => ['integer', Rule::exists('ingredients', 'id')],
            'ingredient_links' => ['sometimes', 'array'],
            'ingredient_links.*.ingredient_id' => ['required_with:ingredient_links', 'integer', Rule::exists('ingredients', 'id')],
            'ingredient_links.*.is_default' => ['sometimes', 'boolean'],
            'ingredient_links.*.is_removable' => ['sometimes', 'boolean'],
            'ingredient_links.*.additional_price' => ['sometimes', 'numeric', 'min:0'],
            'ingredients' => ['sometimes', 'array'],
            'ingredients.*.ingredient_id' => ['required_with:ingredients', 'integer', Rule::exists('ingredients', 'id')],
            'ingredients.*.is_default' => ['sometimes', 'boolean'],
            'ingredients.*.is_removable' => ['sometimes', 'boolean'],
            'ingredients.*.additional_price' => ['sometimes', 'numeric', 'min:0'],
            'extras' => ['sometimes', 'array'],
            'extras.*.id' => ['sometimes', 'integer', Rule::exists('extras', 'id')],
            'extras.*.name' => ['nullable', 'string', 'max:255'],
            'extras.*.additional_price' => ['nullable', 'numeric', 'min:0'],
            'extras.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $product = Product::query()->create(collect($data)->except(['ingredient_ids', 'ingredient_links', 'ingredients', 'extras', 'image'])->all());

        if (!empty($data['ingredients'])) {
            $product->ingredients()->sync($this->buildIngredientSyncPayload($data['ingredients']));
        } elseif (!empty($data['ingredient_links'])) {
            $product->ingredients()->sync($this->buildIngredientSyncPayload($data['ingredient_links']));
        } elseif (!empty($data['ingredient_ids'])) {
            $product->ingredients()->sync($this->buildIngredientSyncPayloadFromIds($data['ingredient_ids']));
        }

        if (!empty($data['extras'])) {
            $this->syncExtras($product, $data['extras']);
        }

        if ($request->hasFile('image')) {
            $product->clearMediaCollection('images');
            $product->addMediaFromRequest('image')->toMediaCollection('images');
        }

        $product->load(['ingredients', 'extras']);
        $product->setAttribute('image_url', $product->getFirstMediaUrl('images') ?: $product->image_url);
        $this->writeAudit($request, 'product.created', 'product', $product->id, [
            'name' => $product->name,
            'base_price' => $product->base_price,
            'category' => $product->category,
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($product->tenant_id)) {
            return response()->json(['message' => 'Producto fuera del tenant actual.'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'base_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'prep_min' => ['sometimes', 'integer', 'min:1'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'min_stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
            'ingredient_ids' => ['sometimes', 'array'],
            'ingredient_ids.*' => ['integer', Rule::exists('ingredients', 'id')],
            'ingredient_links' => ['sometimes', 'array'],
            'ingredient_links.*.ingredient_id' => ['required_with:ingredient_links', 'integer', Rule::exists('ingredients', 'id')],
            'ingredient_links.*.is_default' => ['sometimes', 'boolean'],
            'ingredient_links.*.is_removable' => ['sometimes', 'boolean'],
            'ingredient_links.*.additional_price' => ['sometimes', 'numeric', 'min:0'],
            'ingredients' => ['sometimes', 'array'],
            'ingredients.*.ingredient_id' => ['required_with:ingredients', 'integer', Rule::exists('ingredients', 'id')],
            'ingredients.*.is_default' => ['sometimes', 'boolean'],
            'ingredients.*.is_removable' => ['sometimes', 'boolean'],
            'ingredients.*.additional_price' => ['sometimes', 'numeric', 'min:0'],
            'extras' => ['sometimes', 'array'],
            'extras.*.id' => ['sometimes', 'integer', Rule::exists('extras', 'id')],
            'extras.*.name' => ['nullable', 'string', 'max:255'],
            'extras.*.additional_price' => ['nullable', 'numeric', 'min:0'],
            'extras.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $oldState = [
            'name' => $product->name,
            'base_price' => $product->base_price,
            'category' => $product->category,
            'stock_quantity' => $product->stock_quantity,
            'is_active' => $product->is_active,
        ];

        $product->update(collect($data)->except(['ingredient_ids', 'ingredient_links', 'ingredients', 'extras', 'image'])->all());

        if (array_key_exists('ingredients', $data)) {
            $product->ingredients()->sync($this->buildIngredientSyncPayload($data['ingredients']));
        } elseif (array_key_exists('ingredient_links', $data)) {
            $product->ingredients()->sync($this->buildIngredientSyncPayload($data['ingredient_links']));
        } elseif (array_key_exists('ingredient_ids', $data)) {
            $product->ingredients()->sync($this->buildIngredientSyncPayloadFromIds($data['ingredient_ids']));
        }

        if (array_key_exists('extras', $data)) {
            $this->syncExtras($product, $data['extras']);
        }

        if ($request->hasFile('image')) {
            $product->clearMediaCollection('images');
            $product->addMediaFromRequest('image')->toMediaCollection('images');
        }

        $product->load(['ingredients', 'extras']);
        $product->setAttribute('image_url', $product->getFirstMediaUrl('images') ?: $product->image_url);
        $this->writeAudit($request, 'product.updated', 'product', $product->id, [
            'before' => $oldState,
            'after' => [
                'name' => $product->name,
                'base_price' => $product->base_price,
                'category' => $product->category,
                'stock_quantity' => $product->stock_quantity,
                'is_active' => $product->is_active,
            ],
        ]);

        return response()->json($product);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($product->tenant_id)) {
            return response()->json(['message' => 'Producto fuera del tenant actual.'], 403);
        }

        $snapshot = [
            'name' => $product->name,
            'base_price' => $product->base_price,
            'category' => $product->category,
        ];
        $product->delete();
        $this->writeAudit($request, 'product.deleted', 'product', $product->id, $snapshot);

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    public function bulkUpdatePrices(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', Rule::exists('products', 'id')],
            'mode' => ['required', Rule::in(['percentage', 'fixed_delta'])],
            'value' => ['required', 'numeric'],
            'round_to' => ['nullable', 'integer', Rule::in([1, 5, 10, 50, 100])],
        ]);

        if (empty($data['category']) && empty($data['product_ids'])) {
            return response()->json([
                'message' => 'Debes enviar category o product_ids.',
            ], 422);
        }

        $query = Product::query();
        $this->scopeQueryByTenant($query, $this->resolveTenantId());
        if (!empty($data['category'])) {
            $query->where('category', trim((string) $data['category']));
        }
        if (!empty($data['product_ids'])) {
            $query->whereIn('id', $data['product_ids']);
        }

        $products = $query->get();
        $updated = [];
        $roundTo = (int) ($data['round_to'] ?? 1);

        foreach ($products as $product) {
            $oldPrice = (float) $product->base_price;
            if ($data['mode'] === 'percentage') {
                $newPrice = $oldPrice * (1 + ((float) $data['value'] / 100));
            } else {
                $newPrice = $oldPrice + (float) $data['value'];
            }

            $newPrice = max(0, $newPrice);
            if ($roundTo > 1) {
                $newPrice = round($newPrice / $roundTo) * $roundTo;
            }

            $product->update([
                'base_price' => round($newPrice, 2),
            ]);

            $updated[] = [
                'id' => $product->id,
                'name' => $product->name,
                'old_price' => $oldPrice,
                'new_price' => (float) $product->base_price,
            ];
        }

        $this->writeAudit($request, 'product.bulk_price_update', 'product', null, [
            'criteria' => [
                'category' => $data['category'] ?? null,
                'product_ids' => $data['product_ids'] ?? [],
                'mode' => $data['mode'],
                'value' => (float) $data['value'],
                'round_to' => $roundTo,
            ],
            'affected_count' => count($updated),
            'updated' => $updated,
        ]);

        return response()->json([
            'message' => 'Precios actualizados.',
            'affected_count' => count($updated),
            'updated' => $updated,
        ]);
    }

    private function parseIdList(mixed $value): \Illuminate\Support\Collection
    {
        $list = is_array($value) ? $value : [$value];

        return collect($list)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
    }

    private function buildIngredientSyncPayloadFromIds(array $ingredientIds): array
    {
        return collect($ingredientIds)->mapWithKeys(fn ($id) => [
            (int) $id => [
                'is_default' => true,
                'is_removable' => true,
                'additional_price' => 0,
            ],
        ])->all();
    }

    private function buildIngredientSyncPayload(array $links): array
    {
        return collect($links)->mapWithKeys(function (array $link) {
            $ingredientId = (int) $link['ingredient_id'];

            return [
                $ingredientId => [
                    'is_default' => (bool) ($link['is_default'] ?? true),
                    'is_removable' => (bool) ($link['is_removable'] ?? true),
                    'additional_price' => (float) ($link['additional_price'] ?? 0),
                ],
            ];
        })->all();
    }

    private function syncExtras(Product $product, array $extras): void
    {
        $currentIds = $product->extras()->pluck('id')->all();
        $incomingIds = collect($extras)->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        $toDelete = collect($currentIds)->diff($incomingIds)->values();

        if ($toDelete->isNotEmpty()) {
            $product->extras()->whereIn('id', $toDelete)->delete();
        }

        foreach ($extras as $extraPayload) {
            if (!empty($extraPayload['id'])) {
                $updatePayload = [
                    'additional_price' => (float) ($extraPayload['additional_price'] ?? 0),
                    'is_active' => (bool) ($extraPayload['is_active'] ?? true),
                ];

                if (array_key_exists('name', $extraPayload) && $extraPayload['name']) {
                    $updatePayload['name'] = (string) $extraPayload['name'];
                }

                $product->extras()->where('id', (int) $extraPayload['id'])->update($updatePayload);
                continue;
            }

            $product->extras()->create([
                'name' => (string) ($extraPayload['name'] ?? 'Extra'),
                'additional_price' => (float) ($extraPayload['additional_price'] ?? 0),
                'is_active' => (bool) ($extraPayload['is_active'] ?? true),
            ]);
        }
    }

    private function writeAudit(Request $request, string $action, string $entityType, ?int $entityId, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
        ]);
    }

    private function resolveTenantId(): int
    {
        $userTenantId = (int) (request()->user()?->tenant_id ?? 0);
        if ($userTenantId > 0) {
            return $userTenantId;
        }

        $tenantSlug = trim((string) (request()->query('tenant_slug') ?: request()->header('X-Tenant-Slug') ?: ''));
        if ($tenantSlug !== '') {
            $tenantBySlug = Tenant::query()
                ->where('slug', $tenantSlug)
                ->where('is_active', true)
                ->first();

            if ($tenantBySlug) {
                return (int) $tenantBySlug->id;
            }
        }

        $tenant = Tenant::query()->where('is_active', true)->orderBy('id')->first();
        return (int) ($tenant?->id ?? 0);
    }
    private function scopeQueryByTenant($query, int $tenantId): void
    {
        if ($tenantId <= 0) {
            return;
        }

        $query->where(function ($tenantQuery) use ($tenantId) {
            $tenantQuery->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
        });
    }

    private function belongsToCurrentTenant(?int $resourceTenantId): bool
    {
        $tenantId = $this->resolveTenantId();
        if ($tenantId <= 0) {
            return true;
        }

        return (int) ($resourceTenantId ?? $tenantId) === $tenantId;
    }
}
