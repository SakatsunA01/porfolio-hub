<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function orders(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_email', $request->user()->email)
            ->latest('id')
            ->get([
                'id',
                'order_number',
                'total',
                'payment_status',
                'order_status',
                'created_at',
            ]);

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function showOrder(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_email === $request->user()->email, 404);

        $order->load(['items.product.images', 'items.variant']);

        return response()->json([
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'shipping_address' => trim("{$order->shipping_address}, {$order->shipping_city}, {$order->shipping_postal_code}", ', '),
                'total' => (float) $order->total,
                'items' => $order->items->map(function ($item) {
                    $image = $item->product?->images()
                        ->orderByDesc('is_primary')
                        ->orderBy('position')
                        ->first();

                    return [
                        'id' => $item->id,
                        'name' => $item->product_name_snapshot,
                        'size' => $item->variant?->size,
                        'price' => (float) $item->price_snapshot,
                        'quantity' => (int) $item->quantity,
                        'subtotal' => (float) $item->subtotal,
                        'image' => $image?->image_path,
                        'is_preorder' => $item->product?->type === 'preorder',
                        'preorder_shipping_date' => optional($item->product?->preorder_shipping_date)?->toDateString(),
                    ];
                })->values()->all(),
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->user()->id),
            ],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'data' => $request->user()->fresh(),
        ]);
    }
}
