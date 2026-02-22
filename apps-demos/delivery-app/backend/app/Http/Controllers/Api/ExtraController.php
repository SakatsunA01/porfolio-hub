<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Extra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExtraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $productId = $request->query('product_id');

        $extras = Extra::query()
            ->when($productId, fn ($query) => $query->where('product_id', (int) $productId))
            ->orderBy('name')
            ->get();

        return response()->json($extras);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'additional_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $extra = Extra::query()->create($data);

        return response()->json($extra, 201);
    }

    public function update(Request $request, Extra $extra): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['sometimes', 'integer', Rule::exists('products', 'id')],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'additional_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $extra->update($data);

        return response()->json($extra);
    }

    public function destroy(Extra $extra): JsonResponse
    {
        $extra->delete();

        return response()->json([
            'message' => 'Extra eliminado correctamente.',
        ]);
    }
}
