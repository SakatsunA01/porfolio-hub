<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extra;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExtraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        $productId = $request->query('product_id');

        $query = Extra::query()
            ->orderBy('name')
            ->when($productId, fn ($query) => $query->where('product_id', (int) $productId));
        $this->scopeQueryByTenant($query, $tenantId);

        $extras = $query->get();

        return response()->json($extras);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'additional_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $productQuery = Product::query()->where('id', (int) $data['product_id']);
        $this->scopeQueryByTenant($productQuery, $tenantId);
        if (!$productQuery->exists()) {
            return response()->json(['message' => 'Producto fuera del tenant actual.'], 422);
        }

        $extra = Extra::query()->create($data);

        return response()->json($extra, 201);
    }

    public function update(Request $request, Extra $extra): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        if (!$this->belongsToCurrentTenant($extra->tenant_id)) {
            return response()->json(['message' => 'Extra fuera del tenant actual.'], 403);
        }

        $data = $request->validate([
            'product_id' => ['sometimes', 'integer', Rule::exists('products', 'id')],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'additional_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('product_id', $data)) {
            $productQuery = Product::query()->where('id', (int) $data['product_id']);
            $this->scopeQueryByTenant($productQuery, $tenantId);
            if (!$productQuery->exists()) {
                return response()->json(['message' => 'Producto fuera del tenant actual.'], 422);
            }
        }

        $extra->update($data);

        return response()->json($extra);
    }

    public function destroy(Extra $extra): JsonResponse
    {
        if (!$this->belongsToCurrentTenant($extra->tenant_id)) {
            return response()->json(['message' => 'Extra fuera del tenant actual.'], 403);
        }

        $extra->delete();

        return response()->json([
            'message' => 'Extra eliminado correctamente.',
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
