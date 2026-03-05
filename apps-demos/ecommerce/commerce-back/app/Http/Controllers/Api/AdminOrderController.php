<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class AdminOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = Order::query()
            ->latest('id')
            ->get([
                'id',
                'order_number',
                'user_name',
                'total',
                'payment_status',
                'order_status',
                'created_at',
            ]);

        return response()->json([
            'data' => $orders,
        ]);
    }
}
