<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Combo;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index(): JsonResponse
    {
        $query = Ingredient::query()->orderBy('name');
        $this->scopeQueryByTenant($query, $this->resolveTenantId());

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'additional_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $ingredient = Ingredient::query()->create($data);

        return response()->json($ingredient, 201);
    }

    public function update(Request $request, Ingredient $ingredient): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($ingredient->tenant_id)) {
            return response()->json(['message' => 'Ingrediente fuera del tenant actual.'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'additional_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $ingredient->update($data);

        return response()->json($ingredient);
    }

    public function destroy(Ingredient $ingredient): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($ingredient->tenant_id)) {
            return response()->json(['message' => 'Ingrediente fuera del tenant actual.'], 403);
        }

        $ingredient->delete();

        return response()->json([
            'message' => 'Ingrediente eliminado correctamente.',
        ]);
    }

    public function deactivateGlobal(Request $request, Ingredient $ingredient): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($ingredient->tenant_id)) {
            return response()->json(['message' => 'Ingrediente fuera del tenant actual.'], 403);
        }

        $result = DB::transaction(function () use ($ingredient) {
            $tenantId = $this->resolveTenantId();
            $productIds = $ingredient->products()->pluck('products.id')->all();

            $affectedProducts = 0;
            $affectedCombos = 0;

            if (!empty($productIds)) {
                $productsQuery = Product::query()
                    ->whereIn('id', $productIds)
                    ->where('is_active', true);
                $this->scopeQueryByTenant($productsQuery, $tenantId);
                $affectedProducts = $productsQuery->update(['is_active' => false]);

                $combosQuery = Combo::query()
                    ->whereHas('products', fn ($query) => $query->whereIn('products.id', $productIds))
                    ->where('is_active', true);
                $this->scopeQueryByTenant($combosQuery, $tenantId);
                $affectedCombos = $combosQuery->update(['is_active' => false]);
            }

            $ingredient->update([
                'is_active' => false,
            ]);

            return [
                'affected_products' => $affectedProducts,
                'affected_combos' => $affectedCombos,
            ];
        });

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'ingredient.deactivated_global',
            'entity_type' => 'ingredient',
            'entity_id' => $ingredient->id,
            'metadata' => [
                'ingredient_name' => $ingredient->name,
                ...$result,
            ],
        ]);

        return response()->json([
            'message' => 'Ingrediente desactivado globalmente.',
            ...$result,
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
