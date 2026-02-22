<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BundleController extends Controller
{
    public function index(): JsonResponse
    {
        $bundles = Bundle::query()
            ->with('products')
            ->orderBy('name')
            ->get()
            ->map(function (Bundle $bundle) {
                $bundle->setAttribute('image_url', $bundle->getFirstMediaUrl('images') ?: null);
                return $bundle;
            });

        return response()->json($bundles->values());
    }

    public function show(Bundle $bundle): JsonResponse
    {
        $bundle->load('products');
        $bundle->setAttribute('image_url', $bundle->getFirstMediaUrl('images') ?: null);

        return response()->json($bundle);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pricing_mode' => ['required', Rule::in(['fixed_price', 'discount_percentage'])],
            'fixed_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'products.*.quantity' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $bundle = Bundle::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'pricing_mode' => $data['pricing_mode'],
            'fixed_price' => $data['pricing_mode'] === 'fixed_price' ? ($data['fixed_price'] ?? 0) : null,
            'discount_percentage' => $data['pricing_mode'] === 'discount_percentage' ? ($data['discount_percentage'] ?? 0) : 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $bundle->products()->sync($this->buildSyncPayload($data['products']));

        if ($request->hasFile('image')) {
            $bundle->clearMediaCollection('images');
            $bundle->addMediaFromRequest('image')->toMediaCollection('images');
        }

        $bundle->load('products');
        $bundle->setAttribute('image_url', $bundle->getFirstMediaUrl('images') ?: null);

        return response()->json($bundle, 201);
    }

    public function update(Request $request, Bundle $bundle): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pricing_mode' => ['sometimes', Rule::in(['fixed_price', 'discount_percentage'])],
            'fixed_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'products' => ['sometimes', 'array', 'min:1'],
            'products.*.product_id' => ['required_with:products', 'integer', Rule::exists('products', 'id')],
            'products.*.quantity' => ['nullable', 'integer', 'min:1'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
        ]);

        $pricingMode = $data['pricing_mode'] ?? $bundle->pricing_mode;

        $bundle->update([
            ...$data,
            'pricing_mode' => $pricingMode,
            'fixed_price' => $pricingMode === 'fixed_price' ? ($data['fixed_price'] ?? $bundle->fixed_price) : null,
            'discount_percentage' => $pricingMode === 'discount_percentage' ? ($data['discount_percentage'] ?? $bundle->discount_percentage) : 0,
        ]);

        if (array_key_exists('products', $data)) {
            $bundle->products()->sync($this->buildSyncPayload($data['products']));
        }

        if ($request->hasFile('image')) {
            $bundle->clearMediaCollection('images');
            $bundle->addMediaFromRequest('image')->toMediaCollection('images');
        }

        $bundle->load('products');
        $bundle->setAttribute('image_url', $bundle->getFirstMediaUrl('images') ?: null);

        return response()->json($bundle);
    }

    public function destroy(Bundle $bundle): JsonResponse
    {
        $bundle->delete();

        return response()->json([
            'message' => 'Bundle eliminado correctamente.',
        ]);
    }

    private function buildSyncPayload(array $products): array
    {
        return collect($products)->mapWithKeys(function (array $product) {
            return [
                (int) $product['product_id'] => [
                    'quantity' => max(1, (int) ($product['quantity'] ?? 1)),
                ],
            ];
        })->all();
    }
}

