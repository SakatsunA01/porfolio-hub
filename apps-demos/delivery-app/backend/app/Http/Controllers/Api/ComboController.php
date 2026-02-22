<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComboController extends Controller
{
    public function index(): JsonResponse
    {
        $combos = Combo::query()
            ->with('products')
            ->orderBy('name')
            ->get()
            ->map(function (Combo $combo) {
                $combo->setAttribute('image_url', $combo->getFirstMediaUrl('images') ?: $combo->image_url);
                return $combo;
            });

        return response()->json($combos->values());
    }

    public function show(Combo $combo): JsonResponse
    {
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
}
