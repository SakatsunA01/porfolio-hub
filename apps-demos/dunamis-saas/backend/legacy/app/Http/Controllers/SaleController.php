<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $organizacionId = $this->obtenerOrganizacionId($request);

        $ventas = Sale::query()
            ->with(['user', 'client', 'items.product'])
            ->where('organization_id', $organizacionId)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'ventas' => $ventas,
        ]);
    }

    public function create(Request $request)
    {
        $organizacionId = $this->obtenerOrganizacionId($request);

        $productos = Product::query()
            ->where('organization_id', $organizacionId)
            ->orderBy('name')
            ->get([
                'id',
                'sku',
                'name',
                'description',
                'sale_price',
                'stock_quantity',
                'image_path',
            ]);

        $dolarBlue = ExchangeRate::query()
            ->where('nombre', 'blue')
            ->orderByDesc('updated_at')
            ->first();

        return Inertia::render('Sales/Create', [
            'productos' => $productos,
            'clientes' => Client::query()
                ->where('organization_id', $organizacionId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'dolarBlue' => $dolarBlue?->venta,
        ]);
    }

    public function store(Request $request)
    {
        $organizacionId = $this->obtenerOrganizacionId($request);
        $usuarioId = $request->user()->id;

        $datos = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('organization_id', $organizacionId)),
            ],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('organization_id', $organizacionId)),
            ],
            'moneda_cobro' => ['required', Rule::in(['ARS', 'USD'])],
            'exchange_rate_used' => ['nullable', 'numeric', 'min:0'],
        ]);

        $totalCalculado = collect($datos['items'])->sum(function ($item) {
            return $item['cantidad'] * $item['precio_unitario'];
        });

        $venta = DB::transaction(function () use ($datos, $organizacionId, $usuarioId, $totalCalculado) {
            $venta = Sale::create([
                'organization_id' => $organizacionId,
                'user_id' => $usuarioId,
                'client_id' => $datos['client_id'] ?? null,
                'total_amount' => $totalCalculado,
                'payment_method' => 'cash',
                'status' => 'completed',
                'moneda_cobro' => $datos['moneda_cobro'],
                'exchange_rate_used' => $datos['moneda_cobro'] === 'USD' ? ($datos['exchange_rate_used'] ?? null) : null,
            ]);

            foreach ($datos['items'] as $item) {
                $producto = Product::query()
                    ->where('organization_id', $organizacionId)
                    ->where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $cantidad = $item['cantidad'];
                $producto->decrement('stock_quantity', $cantidad);

                $costoArs = $producto->cost_ars ?? $producto->cost_price ?? 0;

                SaleItem::create([
                    'sale_id' => $venta->id,
                    'product_id' => $producto->id,
                    'product_name' => $producto->name,
                    'quantity' => $cantidad,
                    'unit_price' => $item['precio_unitario'],
                    'cost_ars' => $costoArs,
                    'total_price' => $cantidad * $item['precio_unitario'],
                ]);
            }

            return $venta;
        });

        return redirect()
            ->route('sales.ticket', $venta->id)
            ->with('success', 'Venta registrada correctamente.');
    }

    public function show(Request $request, $id)
    {
        $organizacionId = $this->obtenerOrganizacionId($request);

        $venta = Sale::query()
            ->with(['user', 'client', 'items.product'])
            ->where('organization_id', $organizacionId)
            ->findOrFail($id);

        return Inertia::render('Sales/Show', [
            'venta' => $venta,
        ]);
    }

    public function ticket(Request $request, Sale $sale)
    {
        $organizacionId = $this->obtenerOrganizacionId($request);

        abort_if($sale->organization_id !== $organizacionId, 403, 'No autorizado.');

        $sale->load(['user', 'client', 'items.product']);

        return Inertia::render('Sales/Ticket', [
            'venta' => $sale,
            'organizacion' => $request->user()->organization ?? null,
        ]);
    }

    protected function obtenerOrganizacionId(Request $request): int
    {
        $organizacionId = $request->user()->organization_id;

        abort_if(!$organizacionId, 403, 'El usuario no tiene organización asignada.');

        return $organizacionId;
    }
}
