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
        ]);
    }
}

