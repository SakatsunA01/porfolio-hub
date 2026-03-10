<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends ApiController
{
    public function __invoke(Request $request)
    {
        $organizationId = $this->organizationId($request);
        $today = Carbon::today();

        $salesToday = (float) Sale::query()
            ->where('organization_id', $organizationId)
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $salesCountToday = Sale::query()
            ->where('organization_id', $organizationId)
            ->whereDate('created_at', $today)
            ->count();

        $lowStockCount = Product::query()
            ->where('organization_id', $organizationId)
            ->whereColumn('stock_quantity', '<=', 'min_stock_quantity')
            ->count();

        $latestSales = Sale::query()
            ->with(['user', 'client', 'items'])
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $dolarBlue = ExchangeRate::query()
            ->where('nombre', 'blue')
            ->orderByDesc('updated_at')
            ->first();

        return $this->success([
            'stats' => [
                'sales_today' => $salesToday,
                'sales_count_today' => $salesCountToday,
                'low_stock_count' => $lowStockCount,
                'blue_rate' => $dolarBlue ? (float) $dolarBlue->venta : 0.0,
                'blue_rate_updated_at' => $dolarBlue?->fecha_actualizacion ?? $dolarBlue?->updated_at,
            ],
            'latest_sales' => $latestSales->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'total_amount' => (float) $sale->total_amount,
                'status' => $sale->status,
                'created_at' => $sale->created_at,
                'client_name' => $sale->client?->name ?? 'Consumidor Final',
                'user_name' => $sale->user?->name,
                'items_count' => $sale->items->count(),
            ]),
        ]);
    }
}
