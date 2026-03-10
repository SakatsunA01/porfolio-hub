<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\ProductResource;
use App\Models\ExchangeRate;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $search = (string) $request->query('search', '');
        $perPage = max(1, min(50, (int) $request->query('per_page', 15)));

        $paginator = Product::query()
            ->where('organization_id', $organizationId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($paginator, ProductResource::collection($paginator->items()));
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $validated = $this->validatedPayload($request, $organizationId);
        $costs = $this->normalizeCosts($validated);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::query()->create(array_merge($validated, [
            'organization_id' => $organizationId,
            'image_path' => $imagePath,
            'cost_ars' => $costs['cost_ars'],
            'cost_usd' => $costs['cost_usd'],
            'cost_price' => $costs['cost_ars'],
        ], $this->legacyProductPayload($validated['sale_price'])));

        return $this->success(new ProductResource($product), status: 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        abort_if((int) $product->organization_id !== $organizationId, 404);

        $validated = $this->validatedPayload($request, $organizationId, $product->id);
        $costs = $this->normalizeCosts($validated);

        $payload = array_merge($validated, [
            'cost_ars' => $costs['cost_ars'],
            'cost_usd' => $costs['cost_usd'],
            'cost_price' => $costs['cost_ars'],
        ], $this->legacyProductPayload($validated['sale_price']));

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('products', 'public');
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $payload['image_path'] = $newPath;
        }

        $product->update($payload);

        return $this->success(new ProductResource($product->fresh()));
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        abort_if((int) $product->organization_id !== $organizationId, 404);

        $product->delete();

        return $this->success([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedPayload(Request $request, int $organizationId, ?int $ignoreId = null): array
    {
        $uniqueSku = Rule::unique('products', 'sku')
            ->where(fn ($query) => $query->where('organization_id', $organizationId));

        if ($ignoreId) {
            $uniqueSku->ignore($ignoreId);
        }

        return $request->validate([
            'sku' => ['required', 'string', 'max:255', $uniqueSku],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cost_ars' => ['required_without:cost_usd', 'nullable', 'numeric', 'min:0'],
            'cost_usd' => ['required_without:cost_ars', 'nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'min_stock_quantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{cost_ars: float|null, cost_usd: float|null}
     */
    protected function normalizeCosts(array $validated): array
    {
        $rate = ExchangeRate::query()
            ->where('nombre', 'blue')
            ->orderByDesc('updated_at')
            ->first();

        $exchange = $rate ? (float) $rate->venta : 0.0;
        $costArs = isset($validated['cost_ars']) && $validated['cost_ars'] !== '' ? (float) $validated['cost_ars'] : null;
        $costUsd = isset($validated['cost_usd']) && $validated['cost_usd'] !== '' ? (float) $validated['cost_usd'] : null;

        if ($costArs !== null && $costUsd === null && $exchange > 0) {
            $costUsd = $costArs / $exchange;
        } elseif ($costUsd !== null && $costArs === null && $exchange > 0) {
            $costArs = $costUsd * $exchange;
        }

        return ['cost_ars' => $costArs, 'cost_usd' => $costUsd];
    }

    /**
     * @return array<string, mixed>
     */
    protected function legacyProductPayload(float|string $salePrice): array
    {
        $payload = [];

        if (Schema::hasColumn('products', 'price')) {
            $priceValue = is_numeric($salePrice) ? (float) $salePrice : 0.0;
            $payload['price'] = min($priceValue, 999999.99);
        }

        if (Schema::hasColumn('products', 'commerce_id')) {
            $commerceId = DB::table('commerces')->where('name', 'Dunamis Demo Commerce')->value('id');
            if (!$commerceId) {
                $commerceId = DB::table('commerces')->insertGetId([
                    'name' => 'Dunamis Demo Commerce',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $payload['commerce_id'] = $commerceId;

            if (Schema::hasColumn('products', 'category_id')) {
                $categoryId = DB::table('categories')
                    ->where('commerce_id', $commerceId)
                    ->where('name', 'General')
                    ->value('id');

                if (!$categoryId) {
                    $categoryId = DB::table('categories')->insertGetId([
                        'commerce_id' => $commerceId,
                        'name' => 'General',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $payload['category_id'] = $categoryId;
            }
        }

        return $payload;
    }
}
