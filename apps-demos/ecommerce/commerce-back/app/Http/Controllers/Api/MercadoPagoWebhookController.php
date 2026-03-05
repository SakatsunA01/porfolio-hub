<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ProductStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        protected ProductStockService $productStockService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $paymentId = $this->extractPaymentId($request);

        if (! $paymentId) {
            return response()->json([
                'message' => 'Notification ignored.',
            ], Response::HTTP_ACCEPTED);
        }

        $payment = $this->fetchPayment($paymentId);

        if (! $payment) {
            return response()->json([
                'message' => 'Unable to verify payment.',
            ], Response::HTTP_ACCEPTED);
        }

        $mercadoPagoReference = (string) ($payment['id'] ?? $paymentId);

        $order = Order::query()
            ->with(['items.product.variants', 'items.variant'])
            ->where('mercado_pago_id', $mercadoPagoReference)
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order not found for Mercado Pago reference.',
            ], Response::HTTP_ACCEPTED);
        }

        if ($order->payment_status === 'paid') {
            return response()->json([
                'message' => 'Order already processed.',
            ], Response::HTTP_OK);
        }

        if (($payment['status'] ?? null) !== 'approved') {
            return response()->json([
                'message' => 'Payment not approved.',
            ], Response::HTTP_ACCEPTED);
        }

        DB::transaction(function () use ($order): void {
            $order->refresh();

            if ($order->payment_status === 'paid') {
                return;
            }

            foreach ($order->items as $item) {
                $this->productStockService->decreaseStock(
                    $item->product,
                    $item->variant,
                    $item->quantity,
                );
            }

            $order->forceFill([
                'payment_status' => 'paid',
                'order_status' => 'confirmed',
            ])->save();
        });

        return response()->json([
            'message' => 'Webhook processed.',
        ], Response::HTTP_OK);
    }

    protected function extractPaymentId(Request $request): ?string
    {
        return $request->input('data.id')
            ?? $request->input('resource.id')
            ?? $request->input('id')
            ?? null;
    }

    protected function fetchPayment(string $paymentId): ?array
    {
        $accessToken = config('services.mercadopago.access_token', env('MERCADO_PAGO_ACCESS_TOKEN'));

        if (! $accessToken) {
            return null;
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }
}
