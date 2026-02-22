<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Combo;
use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Ingredient::query()->orderBy('name')->get()
        );
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
        $ingredient->delete();

        return response()->json([
            'message' => 'Ingrediente eliminado correctamente.',
        ]);
    }

    public function deactivateGlobal(Request $request, Ingredient $ingredient): JsonResponse
    {
        $result = DB::transaction(function () use ($ingredient) {
            $productIds = $ingredient->products()->pluck('products.id')->all();

            $affectedProducts = 0;
            $affectedCombos = 0;

            if (!empty($productIds)) {
                $affectedProducts = Product::query()
                    ->whereIn('id', $productIds)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);

                $affectedCombos = Combo::query()
                    ->whereHas('products', fn ($query) => $query->whereIn('products.id', $productIds))
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
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
}
