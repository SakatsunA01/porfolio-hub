<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ProductStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        protected ProductStockService $productStockService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            Log::warning('mercado_pago_webhook_invalid_signature', [
                'request_id' => $request->attributes->get('request_id'),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Invalid webhook signature.',
            ], Response::HTTP_ACCEPTED);
        }

        $paymentId = $this->extractPaymentId($request);

        if (! $paymentId) {
            Log::info('mercado_pago_webhook_ignored', [
                'request_id' => $request->attributes->get('request_id'),
                'reason' => 'missing_payment_id',
            ]);
            return response()->json([
                'message' => 'Notification ignored.',
            ], Response::HTTP_ACCEPTED);
        }

        $payment = $this->fetchPayment($paymentId);

        if (! $payment) {
            Log::warning('mercado_pago_webhook_payment_verification_failed', [
                'request_id' => $request->attributes->get('request_id'),
                'payment_id' => $paymentId,
            ]);
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
            Log::warning('mercado_pago_webhook_order_not_found', [
                'request_id' => $request->attributes->get('request_id'),
                'mp_reference' => $mercadoPagoReference,
            ]);
            return response()->json([
                'message' => 'Order not found for Mercado Pago reference.',
            ], Response::HTTP_ACCEPTED);
        }

        if ($order->payment_status === 'paid') {
            Log::info('mercado_pago_webhook_idempotent_skip', [
                'request_id' => $request->attributes->get('request_id'),
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
            ]);
            return response()->json([
                'message' => 'Order already processed.',
            ], Response::HTTP_OK);
        }

        if (($payment['status'] ?? null) !== 'approved') {
            Log::info('mercado_pago_webhook_payment_not_approved', [
                'request_id' => $request->attributes->get('request_id'),
                'order_id' => $order->id,
                'payment_id' => $mercadoPagoReference,
                'status' => $payment['status'] ?? null,
            ]);
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

        Log::info('mercado_pago_webhook_processed', [
            'request_id' => $request->attributes->get('request_id'),
            'order_id' => $order->id,
            'payment_id' => $mercadoPagoReference,
            'status' => $payment['status'] ?? null,
        ]);

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

    protected function hasValidSignature(Request $request): bool
    {
        $secret = trim((string) env('MERCADO_PAGO_WEBHOOK_SECRET', ''));
        if ($secret === '') {
            return true;
        }

        $signatureHeader = trim((string) $request->header('X-Signature', ''));
        $requestId = trim((string) $request->header('X-Request-Id', ''));
        $paymentId = (string) ($this->extractPaymentId($request) ?? '');

        if ($signatureHeader === '' || $requestId === '' || $paymentId === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $entry) {
            [$key, $value] = array_pad(explode('=', trim($entry), 2), 2, '');
            if ($key !== '' && $value !== '') {
                $parts[$key] = $value;
            }
        }

        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';
        if ($ts === '' || $v1 === '') {
            return false;
        }

        $manifest = "id:{$paymentId};request-id:{$requestId};ts:{$ts};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }
}
