<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $organizacionId = $request->user()->organization_id;
        abort_if(!$organizacionId, 403, 'El usuario no tiene organización asignada.');

        $hoy = Carbon::today();

        $ventasHoy = Sale::query()
            ->where('organization_id', $organizacionId)
            ->whereDate('created_at', $hoy)
            ->sum('total_amount');

        $cantidadVentasHoy = Sale::query()
            ->where('organization_id', $organizacionId)
            ->whereDate('created_at', $hoy)
            ->count();

        $stockBajo = Product::query()
            ->where('organization_id', $organizacionId)
            ->whereColumn('stock_quantity', '<=', 'min_stock_quantity')
            ->count();

        $ultimasVentas = Sale::query()
            ->with(['user', 'client'])
            ->where('organization_id', $organizacionId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $dolarBlue = ExchangeRate::query()
            ->where('nombre', 'blue')
            ->orderByDesc('updated_at')
            ->first();

        return Inertia::render('Dashboard', [
            'ventas_hoy' => $ventasHoy,
            'cantidad_ventas_hoy' => $cantidadVentasHoy,
            'stock_bajo' => $stockBajo,
            'ultimas_ventas' => $ultimasVentas,
            'dolar_blue' => $dolarBlue ? (float) $dolarBlue->venta : 0,
            'dolar_blue_actualizado' => $dolarBlue?->fecha_actualizacion ?? $dolarBlue?->updated_at,
        ]);
    }
}
