<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StorefrontOrderController extends Controller
{
    public function __construct(
        protected ProductStockService $productStockService,
    ) {
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $this->validateOrderPayload($request, false);
        $breakdown = $this->buildOrderBreakdown($validated['items'], $validated['shipping_method'] ?? 'delivery');

        return response()->json([
            'data' => [
                'items' => $breakdown['items'],
                'totals' => $breakdown['totals'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateOrderPayload($request, true);
        $breakdown = $this->buildOrderBreakdown($validated['items'], $validated['shipping_method'] ?? 'delivery');

        $order = DB::transaction(function () use ($validated, $breakdown) {
            $order = Order::query()->create([
                'order_number' => $this->nextOrderNumber(),
                'user_name' => $validated['customer']['name'],
                'user_email' => $validated['customer']['email'],
                'user_phone' => $validated['customer']['phone'] ?? null,
                'shipping_address' => $validated['shipping']['address'] ?? 'Retiro en local',
                'shipping_city' => $validated['shipping']['city'] ?? 'Retiro en local',
                'shipping_postal_code' => $validated['shipping']['postal_code'] ?? '0000',
                'total' => $breakdown['totals']['total'],
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'mercado_pago_id' => null,
            ]);

            $order->items()->createMany($breakdown['items']);

            return $order->load('items');
        });

        return response()->json([
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'total' => (float) $order->total,
            ],
        ], 201);
    }

    public function validateAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:180'],
            'city' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:20'],
        ]);

        $isLikelyValid = mb_strlen(trim($validated['address'])) >= 5
            && mb_strlen(trim($validated['city'])) >= 2
            && mb_strlen(trim($validated['postal_code'])) >= 4;

        return response()->json([
            'data' => [
                'valid' => $isLikelyValid,
                'message' => $isLikelyValid
                    ? 'Direccion validada.'
                    : 'No pudimos validar la direccion. Revisa los datos.',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateOrderPayload(Request $request, bool $requireCustomerAndShipping): array
    {
        $rules = [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'shipping_method' => ['nullable', 'in:delivery,pickup'],
        ];

        if ($requireCustomerAndShipping) {
            $rules = array_merge($rules, [
                'customer' => ['required', 'array'],
                'customer.name' => ['required', 'string', 'max:120'],
                'customer.email' => ['required', 'email', 'max:180'],
                'customer.phone' => ['nullable', 'string', 'max:40'],
                'shipping' => ['nullable', 'array'],
                'shipping.address' => ['required_if:shipping_method,delivery', 'nullable', 'string', 'max:180'],
                'shipping.city' => ['required_if:shipping_method,delivery', 'nullable', 'string', 'max:120'],
                'shipping.postal_code' => ['required_if:shipping_method,delivery', 'nullable', 'string', 'max:20'],
            ]);
        }

        return $request->validate($rules);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     totals: array{subtotal: float, shipping: float, total: float}
     * }
     *
     * @throws ValidationException
     */
    protected function buildOrderBreakdown(array $items, string $shippingMethod): array
    {
        $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
        $variantIds = collect($items)->pluck('variant_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $products = Product::query()
            ->with('variants')
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $variants = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        $orderItems = [];
        $subtotal = 0.0;

        foreach ($items as $index => $item) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'Producto no disponible.',
                ]);
            }

            $variant = null;
            if (!empty($item['variant_id'])) {
                $variant = $variants->get((int) $item['variant_id']);
            }

            try {
                $this->productStockService->checkAvailability($product, $variant, (int) $item['quantity']);
            } catch (InsufficientStockException $exception) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => $exception->getMessage(),
                ]);
            }

            $price = (float) $product->price;
            $quantity = (int) $item['quantity'];
            $lineSubtotal = $price * $quantity;
            $subtotal += $lineSubtotal;

            $orderItems[] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'product_name_snapshot' => $product->name,
                'price_snapshot' => $price,
                'quantity' => $quantity,
                'subtotal' => $lineSubtotal,
            ];
        }

        $shipping = $shippingMethod === 'pickup' ? 0.0 : 12000.0;
        $total = $subtotal + $shipping;

        return [
            'items' => $orderItems,
            'totals' => [
                'subtotal' => round($subtotal, 2),
                'shipping' => round($shipping, 2),
                'total' => round($total, 2),
            ],
        ];
    }

    protected function nextOrderNumber(): string
    {
        $lastOrderId = (int) Order::query()->max('id');
        $next = $lastOrderId + 1;

        return sprintf('ORD-%04d', $next);
    }
}
