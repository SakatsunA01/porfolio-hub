<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = $this->organizationId($request);
        $search = $request->input('search');
        $tasaBlue = ExchangeRate::query()
            ->where('nombre', 'blue')
            ->orderByDesc('updated_at')
            ->first();

        $products = Product::query()
            ->where('organization_id', $organizationId)
            ->when($search, function ($query, $value) {
                $query->where(function ($subQuery) use ($value) {
                    $subQuery
                        ->where('sku', 'like', "%{$value}%")
                        ->orWhere('name', 'like', "%{$value}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => $search,
            ],
            'tasaBlue' => $tasaBlue ? (float) $tasaBlue->venta : 0,
        ]);
    }

    public function store(Request $request)
    {
        $organizationId = $this->organizationId($request);

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->where(function ($query) use ($organizationId) {
                    return $query->where('organization_id', $organizationId);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cost_ars' => ['required_without:cost_usd', 'nullable', 'numeric', 'min:0'],
            'cost_usd' => ['required_without:cost_ars', 'nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'min_stock_quantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $rate = ExchangeRate::query()
            ->where('nombre', 'blue')
            ->orderByDesc('updated_at')
            ->first();

        $tasa = $rate ? (float) $rate->venta : 0;
        $costArs = $validated['cost_ars'] !== null && $validated['cost_ars'] !== '' ? (float) $validated['cost_ars'] : null;
        $costUsd = $validated['cost_usd'] !== null && $validated['cost_usd'] !== '' ? (float) $validated['cost_usd'] : null;

        if ($costArs !== null && $costUsd === null && $tasa > 0) {
            $costUsd = $costArs / $tasa;
        } elseif ($costUsd !== null && $costArs === null && $tasa > 0) {
            $costArs = $costUsd * $tasa;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create(array_merge($validated, [
            'organization_id' => $organizationId,
            'image_path' => $imagePath,
            'cost_ars' => $costArs,
            'cost_usd' => $costUsd ?: null,
            'cost_price' => $costArs,
        ]));

        return redirect()
            ->back()
            ->with('success', 'Producto guardado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $organizationId = $this->organizationId($request);

        $product = Product::query()
            ->where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where(function ($query) use ($organizationId) {
                        return $query->where('organization_id', $organizationId);
                    })
                    ->ignore($product->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cost_ars' => ['required_without:cost_usd', 'nullable', 'numeric', 'min:0'],
            'cost_usd' => ['required_without:cost_ars', 'nullable', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'min_stock_quantity' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $rate = ExchangeRate::query()
            ->where('nombre', 'blue')
            ->orderByDesc('updated_at')
            ->first();

        $tasa = $rate ? (float) $rate->venta : 0;
        $costArs = $validated['cost_ars'] !== null && $validated['cost_ars'] !== '' ? (float) $validated['cost_ars'] : null;
        $costUsd = $validated['cost_usd'] !== null && $validated['cost_usd'] !== '' ? (float) $validated['cost_usd'] : null;

        if ($costArs !== null && $costUsd === null && $tasa > 0) {
            $costUsd = $costArs / $tasa;
        } elseif ($costUsd !== null && $costArs === null && $tasa > 0) {
            $costArs = $costUsd * $tasa;
        }

        $data = array_merge($validated, [
            'cost_ars' => $costArs,
            'cost_usd' => $costUsd ?: null,
            'cost_price' => $costArs,
        ]);

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('products', 'public');
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $newPath;
        }

        $product->update($data);

        return redirect()
            ->back()
            ->with('success', 'Producto guardado correctamente.');
    }

    public function destroy(Request $request, $id)
    {
        $organizationId = $this->organizationId($request);

        $product = Product::query()
            ->where('organization_id', $organizationId)
            ->where('id', $id)
            ->firstOrFail();

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }

    protected function organizationId(Request $request): int
    {
        $organizationId = $request->user()->organization_id;

        abort_if(!$organizationId, 403, 'El usuario no tiene organización asignada.');

        return $organizationId;
    }
}
