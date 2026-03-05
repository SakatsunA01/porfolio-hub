<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class StorefrontController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Tienda no encontrada.',
            ], 404);
        }

        return response()->json([
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'is_active' => (bool) $tenant->is_active,
            'shipping_fee_ars' => (int) ($tenant->shipping_fee_ars ?? 0),
            'free_shipping_threshold_ars' => (int) ($tenant->free_shipping_threshold_ars ?? 0),
            'logo_url' => $tenant->logo_url,
            'brand_theme_key' => (string) ($tenant->brand_theme_key ?: 'green'),
            'brand_primary_color' => (string) ($tenant->brand_primary_color ?: '#10B981'),
            'business_phone' => (string) ($tenant->business_phone ?? ''),
            'business_address' => (string) ($tenant->business_address ?? ''),
        ]);
    }
}
