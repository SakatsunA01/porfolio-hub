<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function profit(Request $request)
    {
        $organizacionId = $request->user()->organization_id;
        abort_if(!$organizacionId, 403, 'El usuario no tiene organización asignada.');

        $from = $request->input('from');
        $to = $request->input('to');

        $ventas = Sale::query()
            ->with(['client', 'items'])
            ->where('organization_id', $organizacionId)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(function ($sale) {
                $costoTotal = $sale->items->sum(function ($item) {
                    return ($item->cost_ars ?? 0) * $item->quantity;
                });

                $ganancia = (float) $sale->total_amount - $costoTotal;
                $margen = $sale->total_amount > 0 ? ($ganancia / $sale->total_amount) * 100 : 0;

                return [
                    'id' => $sale->id,
                    'created_at' => $sale->created_at,
                    'cliente' => $sale->client?->name ?? 'Consumidor Final',
                    'total' => (float) $sale->total_amount,
                    'costo_total' => round($costoTotal, 2),
                    'ganancia' => round($ganancia, 2),
                    'margen' => round($margen, 2),
                ];
            });

        return Inertia::render('Reports/Profit', [
            'ventas' => $ventas,
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }
}
