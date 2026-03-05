<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $monthStart = now()->startOfMonth();

        $salesThisMonth = (float) Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $monthStart)
            ->sum('total');

        $pendingOrders = Order::query()
            ->whereIn('order_status', ['pending', 'confirmed'])
            ->count();

        $outOfStockProducts = Product::query()
            ->where('type', '!=', 'preorder')
            ->where(function ($query) {
                $query
                    ->where(function ($subQuery) {
                        $subQuery->whereDoesntHave('variants')
                            ->where(function ($stockQuery) {
                                $stockQuery->whereNull('stock_global')
                                    ->orWhere('stock_global', '<=', 0);
                            });
                    })
                    ->orWhereHas('variants', fn ($variantQuery) => $variantQuery->select('product_id')
                        ->groupBy('product_id')
                        ->havingRaw('SUM(stock) <= 0'));
            })
            ->count();

        $activePreorders = Product::query()
            ->where('type', 'preorder')
            ->where('is_active', true)
            ->count();

        return response()->json([
            'data' => [
                'sales_this_month' => $salesThisMonth,
                'pending_orders' => $pendingOrders,
                'out_of_stock_products' => $outOfStockProducts,
                'active_preorders' => $activePreorders,
            ],
        ]);
    }
}
