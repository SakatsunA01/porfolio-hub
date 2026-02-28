<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComboController extends Controller
{
    public function index(): JsonResponse
    {
        $query = Combo::query()
            ->with('products')
            ->orderBy('name');
        $this->scopeQueryByTenant($query, $this->resolveTenantId());

        $combos = $query
            ->get()
            ->map(function (Combo $combo) {
                $combo->setAttribute('image_url', $combo->getFirstMediaUrl('images') ?: $combo->image_url);
                return $combo;
            });

        return response()->json($combos->values());
    }

    public function show(Combo $combo): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($combo->tenant_id)) {
            return response()->json(['message' => 'Combo fuera del tenant actual.'], 403);
        }

        $combo->load([
                'products.ingredients' => fn ($query) => $query->where('ingredients.is_active', true),
                'products.extras' => fn ($query) => $query->where('is_active', true),
            ]);
        $combo->setAttribute('image_url', $combo->getFirstMediaUrl('images') ?: $combo->image_url);

        return response()->json($combo);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'products.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        if (!$this->productsBelongToCurrentTenant($data['products'])) {
            return response()->json(['message' => 'Al menos un producto no pertenece al tenant actual.'], 422);
        }

        $combo = Combo::query()->create(collect($data)->except(['products', 'image'])->all());
        $combo->products()->sync($this->buildSyncPayload($data['products']));

        if ($request->hasFile('image')) {
            $combo->clearMediaCollection('images');
            $combo->addMediaFromRequest('image')->toMediaCollection('images');
        }

        $combo->load('products');
        $combo->setAttribute('image_url', $combo->getFirstMediaUrl('images') ?: $combo->image_url);

        return response()->json($combo, 201);
    }

    public function update(Request $request, Combo $combo): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($combo->tenant_id)) {
            return response()->json(['message' => 'Combo fuera del tenant actual.'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'base_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
            'products' => ['sometimes', 'array', 'min:1'],
            'products.*.product_id' => ['required_with:products', 'integer', Rule::exists('products', 'id')],
            'products.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        if (array_key_exists('products', $data) && !$this->productsBelongToCurrentTenant($data['products'])) {
            return response()->json(['message' => 'Al menos un producto no pertenece al tenant actual.'], 422);
        }

        $combo->update(collect($data)->except(['products', 'image'])->all());

        if (array_key_exists('products', $data)) {
            $combo->products()->sync($this->buildSyncPayload($data['products']));
        }

        if ($request->hasFile('image')) {
            $combo->clearMediaCollection('images');
            $combo->addMediaFromRequest('image')->toMediaCollection('images');
        }

        $combo->load('products');
        $combo->setAttribute('image_url', $combo->getFirstMediaUrl('images') ?: $combo->image_url);

        return response()->json($combo);
    }

    public function destroy(Combo $combo): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($combo->tenant_id)) {
            return response()->json(['message' => 'Combo fuera del tenant actual.'], 403);
        }

        $combo->delete();

        return response()->json([
            'message' => 'Combo eliminado correctamente.',
        ]);
    }

    private function buildSyncPayload(array $items): array
    {
        return collect($items)->mapWithKeys(function ($item) {
            $productId = (int) $item['product_id'];

            return [
                $productId => [
                    'quantity' => (int) ($item['quantity'] ?? 1),
                ],
            ];
        })->all();
    }

    private function productsBelongToCurrentTenant(array $items): bool
    {
        $tenantId = $this->resolveTenantId();
        if ($tenantId <= 0) {
            return true;
        }

        $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
        if ($productIds->isEmpty()) {
            return true;
        }

        $count = Product::query()
            ->whereIn('id', $productIds)
            ->where(function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            })
            ->count();

        return $count === $productIds->count();
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
