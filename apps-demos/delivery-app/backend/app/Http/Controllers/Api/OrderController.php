<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Combo;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Order::query()->latest()->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'payment_method' => ['sometimes', Rule::in(['cash', 'mercado_pago'])],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'items.*.combo_id' => ['nullable', 'integer', Rule::exists('combos', 'id')],
            'items.*.bundle_id' => ['nullable', 'integer', Rule::exists('bundles', 'id')],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.excluded_ingredients' => ['sometimes', 'array'],
            'items.*.excluded_ingredients.*' => ['integer', Rule::exists('ingredients', 'id')],
            'items.*.extras' => ['sometimes', 'array'],
            'items.*.extras.*' => ['integer', Rule::exists('extras', 'id')],
            'items.*.sub_items' => ['sometimes', 'array'],
            'items.*.sub_items.*.product_id' => ['required_with:items.*.sub_items', 'integer', Rule::exists('products', 'id')],
            'items.*.sub_items.*.excluded_ingredients' => ['sometimes', 'array'],
            'items.*.sub_items.*.excluded_ingredients.*' => ['integer', Rule::exists('ingredients', 'id')],
            'items.*.sub_items.*.extras' => ['sometimes', 'array'],
            'items.*.sub_items.*.extras.*' => ['integer', Rule::exists('extras', 'id')],
        ]);

        $normalizedItems = collect($data['items'])
            ->map(fn (array $item) => $this->buildOrderItemPayload($item))
            ->values();

        $customerKey = mb_strtolower(trim((string) $data['customer']));
        $profile = CustomerProfile::query()->where('customer_key', $customerKey)->first();
        if ($profile?->is_blocked) {
            throw ValidationException::withMessages([
                'customer' => ['Este cliente esta bloqueado temporalmente.'],
            ]);
        }

        $subtotal = (float) $normalizedItems->sum('line_subtotal');
        $extrasTotal = (float) $normalizedItems->sum('line_extras_total');
        $total = round($subtotal + $extrasTotal, 2);
        $etaMin = $this->estimateEta($normalizedItems->toArray());
        $paymentMethod = $data['payment_method'] ?? 'cash';
        $cashReceived = array_key_exists('cash_received', $data) ? (float) $data['cash_received'] : null;
        $changeAmount = 0.0;

        if ($paymentMethod === 'cash' && $cashReceived !== null && $cashReceived > $total) {
            $changeAmount = round($cashReceived - $total, 2);
        }

        $order = DB::transaction(function () use ($data, $paymentMethod, $normalizedItems, $subtotal, $extrasTotal, $total, $cashReceived, $changeAmount, $etaMin) {
            $stockDemand = $this->collectProductStockDemand($normalizedItems->toArray());
            $this->reserveStockOrFail($stockDemand);

            $order = Order::query()->create([
                'customer' => $data['customer'],
                'address' => $data['address'],
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentMethod === 'mercado_pago' ? 'paid' : 'pending',
                'items' => $normalizedItems->toArray(),
                'subtotal' => round($subtotal, 2),
                'extras_total' => round($extrasTotal, 2),
                'total' => $total,
                'cash_received' => $cashReceived,
                'change_amount' => $changeAmount,
                'status' => 'pendiente',
                'eta_min' => $etaMin,
            ]);

            $this->createOrderItemSnapshots($order, $normalizedItems->toArray());
            CustomerProfile::query()->updateOrCreate(
                ['customer_key' => $customerKey],
                [
                    'display_name' => $data['customer'],
                    'last_address' => $data['address'],
                ]
            );
            $this->writeAudit($request, 'order.created', 'order', $order->id, [
                'customer' => $order->customer,
                'total' => $order->total,
                'status' => $order->status,
                'items_count' => count($normalizedItems->toArray()),
            ]);

            return $order;
        });

        return response()->json($order, 201);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(['pendiente', 'preparando', 'listo', 'en_camino', 'entregado', 'cancelado', 'rechazado'])],
            'employee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'driver_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $order->update([
            'status' => $data['status'],
            'employee_id' => $data['employee_id'] ?? $order->employee_id,
            'driver_id' => $data['driver_id'] ?? $order->driver_id,
            'eta_min' => in_array($data['status'], ['entregado', 'cancelado', 'rechazado'], true) ? 0 : $order->eta_min,
        ]);
        $this->writeAudit($request, 'order.status_updated', 'order', $order->id, [
            'status' => $order->status,
            'employee_id' => $order->employee_id,
            'driver_id' => $order->driver_id,
        ]);

        return response()->json($order->fresh());
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'customer' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(['pendiente', 'preparando', 'listo', 'en_camino', 'entregado', 'cancelado', 'rechazado'])],
            'employee_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'driver_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'payment_method' => ['sometimes', Rule::in(['cash', 'mercado_pago'])],
            'payment_status' => ['sometimes', Rule::in(['pending', 'paid', 'refunded'])],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
            'eta_min' => ['sometimes', 'integer', 'min:0'],
        ]);

        $payload = collect($data)->only([
            'customer',
            'address',
            'status',
            'employee_id',
            'driver_id',
            'payment_method',
            'payment_status',
            'cash_received',
            'eta_min',
        ])->all();

        if (array_key_exists('payment_method', $payload) && $payload['payment_method'] === 'mercado_pago') {
            $payload['change_amount'] = 0;
            if (!array_key_exists('payment_status', $payload)) {
                $payload['payment_status'] = 'paid';
            }
        } elseif (array_key_exists('cash_received', $payload)) {
            $cashReceived = (float) $payload['cash_received'];
            $payload['change_amount'] = $cashReceived > (float) $order->total
                ? round($cashReceived - (float) $order->total, 2)
                : 0;
        }

        $before = [
            'customer' => $order->customer,
            'address' => $order->address,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
        ];

        $order->update($payload);
        $this->writeAudit($request, 'order.updated', 'order', $order->id, [
            'before' => $before,
            'after' => [
                'customer' => $order->customer,
                'address' => $order->address,
                'status' => $order->status,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
            ],
        ]);

        return response()->json($order->fresh());
    }

    public function updatePayment(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'payment_status' => ['required', Rule::in(['pending', 'paid', 'refunded'])],
            'cash_received' => ['nullable', 'numeric', 'min:0'],
        ]);

        $cashReceived = array_key_exists('cash_received', $data)
            ? (float) $data['cash_received']
            : (float) ($order->cash_received ?? 0);

        $changeAmount = $order->payment_method === 'cash' && $cashReceived > (float) $order->total
            ? round($cashReceived - (float) $order->total, 2)
            : 0;

        $order->update([
            'payment_status' => $data['payment_status'],
            'cash_received' => array_key_exists('cash_received', $data) ? $cashReceived : $order->cash_received,
            'change_amount' => $changeAmount,
        ]);
        $this->writeAudit($request, 'order.payment_updated', 'order', $order->id, [
            'payment_status' => $order->payment_status,
            'cash_received' => $order->cash_received,
            'change_amount' => $order->change_amount,
        ]);

        return response()->json($order->fresh());
    }

    private function buildOrderItemPayload(array $item): array
    {
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $comboId = isset($item['combo_id']) ? (int) $item['combo_id'] : null;
        $bundleId = isset($item['bundle_id']) ? (int) $item['bundle_id'] : null;
        $productId = isset($item['product_id']) ? (int) $item['product_id'] : null;

        if ($bundleId) {
            return $this->buildBundleOrderItem($bundleId, $qty);
        }

        if ($comboId) {
            return $this->buildComboOrderItem($comboId, $qty, $item);
        }

        if (!$productId) {
            abort(422, 'Cada item debe incluir product_id o combo_id.');
        }

        $product = Product::query()
            ->with([
                'ingredients' => fn ($query) => $query->where('ingredients.is_active', true),
                'extras' => fn ($query) => $query->where('is_active', true),
            ])
            ->findOrFail($productId);

        return $this->buildSingleProductOrderItem($product, $qty, $item);
    }

    private function buildSingleProductOrderItem(Product $product, int $qty, array $item): array
    {
        $requestedExcluded = collect($item['excluded_ingredients'] ?? [])->map(fn ($id) => (int) $id)->filter();
        $requestedExtras = collect($item['extras'] ?? [])->map(fn ($id) => (int) $id)->filter();

        $removableIncluded = $product->ingredients
            ->filter(fn ($ingredient) => (bool) $ingredient->pivot?->is_default && (bool) $ingredient->pivot?->is_removable)
            ->pluck('id');

        $effectiveExcluded = $removableIncluded->intersect($requestedExcluded)->values();
        $selectedExtras = $product->extras->whereIn('id', $requestedExtras)->values();

        $basePrice = (float) $product->base_price;
        $extrasPerUnit = (float) $selectedExtras->sum('additional_price');

        $unitPrice = round($basePrice + $extrasPerUnit, 2);
        $lineSubtotal = round($basePrice * $qty, 2);
        $lineExtras = round($extrasPerUnit * $qty, 2);
        $lineTotal = round($lineSubtotal + $lineExtras, 2);

        return [
            'type' => 'product',
            'product_id' => $product->id,
            'name' => $product->name,
            'qty' => $qty,
            'excluded_ingredients' => $effectiveExcluded->values()->all(),
            'extras' => $selectedExtras->map(fn ($extra) => [
                'id' => (int) $extra->id,
                'name' => (string) $extra->name,
                'additional_price' => (float) $extra->additional_price,
            ])->values()->all(),
            'unit_base_price' => $basePrice,
            'unit_extras_total' => $extrasPerUnit,
            'unit_price' => $unitPrice,
            'line_subtotal' => $lineSubtotal,
            'line_extras_total' => $lineExtras,
            'line_total' => $lineTotal,
            'prep_weight' => ((int) $product->prep_min) * $qty,
        ];
    }

    private function buildComboOrderItem(int $comboId, int $qty, array $item): array
    {
        $combo = Combo::query()
            ->with([
                'products.ingredients' => fn ($query) => $query->where('ingredients.is_active', true),
                'products.extras' => fn ($query) => $query->where('is_active', true),
            ])
            ->findOrFail($comboId);

        $subItemsInput = collect($item['sub_items'] ?? [])->keyBy(fn ($row) => (int) ($row['product_id'] ?? 0));

        $productsSubtotalPerUnit = 0.0;
        $productsExtrasPerUnit = 0.0;
        $prepWeight = 0;
        $subItems = [];

        foreach ($combo->products as $product) {
            $pivotQty = max(1, (int) ($product->pivot?->quantity ?? 1));
            $subConfig = $subItemsInput->get($product->id, []);
            $subPayload = $this->buildSingleProductOrderItem($product, $pivotQty, $subConfig);

            $productsSubtotalPerUnit += (float) $subPayload['line_subtotal'];
            $productsExtrasPerUnit += (float) $subPayload['line_extras_total'];
            $prepWeight += (int) $subPayload['prep_weight'];

            $subItems[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'qty' => $pivotQty,
                'excluded_ingredients' => $subPayload['excluded_ingredients'],
                'extras' => $subPayload['extras'],
            ];
        }

        $comboBasePerUnit = (float) ($combo->base_price > 0 ? $combo->base_price : $productsSubtotalPerUnit);
        $unitPrice = round($comboBasePerUnit + $productsExtrasPerUnit, 2);
        $lineSubtotal = round($comboBasePerUnit * $qty, 2);
        $lineExtras = round($productsExtrasPerUnit * $qty, 2);
        $lineTotal = round($lineSubtotal + $lineExtras, 2);

        return [
            'type' => 'combo',
            'combo_id' => $combo->id,
            'name' => $combo->name,
            'qty' => $qty,
            'sub_items' => $subItems,
            'unit_base_price' => $comboBasePerUnit,
            'unit_extras_total' => $productsExtrasPerUnit,
            'unit_price' => $unitPrice,
            'line_subtotal' => $lineSubtotal,
            'line_extras_total' => $lineExtras,
            'line_total' => $lineTotal,
            'prep_weight' => $prepWeight * $qty,
        ];
    }

    private function buildBundleOrderItem(int $bundleId, int $qty): array
    {
        $bundle = Bundle::query()->with('products')->findOrFail($bundleId);

        $productsSubtotalPerUnit = 0.0;
        $prepWeight = 0;
        $bundleItems = [];

        foreach ($bundle->products as $product) {
            $pivotQty = max(1, (int) ($product->pivot?->quantity ?? 1));
            $productsSubtotalPerUnit += ((float) $product->base_price * $pivotQty);
            $prepWeight += ((int) $product->prep_min * $pivotQty);
            $bundleItems[] = [
                'product_id' => (int) $product->id,
                'qty' => $pivotQty,
            ];
        }

        if ($bundle->pricing_mode === 'discount_percentage') {
            $discount = max(0, min(100, (float) $bundle->discount_percentage));
            $bundleBasePerUnit = round($productsSubtotalPerUnit * (1 - ($discount / 100)), 2);
        } else {
            $bundleBasePerUnit = (float) ($bundle->fixed_price ?? 0);
        }

        $lineSubtotal = round($bundleBasePerUnit * $qty, 2);

        return [
            'type' => 'bundle',
            'bundle_id' => $bundle->id,
            'name' => $bundle->name,
            'qty' => $qty,
            'unit_base_price' => $bundleBasePerUnit,
            'unit_extras_total' => 0,
            'unit_price' => $bundleBasePerUnit,
            'line_subtotal' => $lineSubtotal,
            'line_extras_total' => 0,
            'line_total' => $lineSubtotal,
            'prep_weight' => $prepWeight * $qty,
            'bundle_items' => $bundleItems,
            'bundle_snapshot' => [
                'pricing_mode' => $bundle->pricing_mode,
                'fixed_price' => (float) ($bundle->fixed_price ?? 0),
                'discount_percentage' => (float) ($bundle->discount_percentage ?? 0),
            ],
        ];
    }

    private function estimateEta(array $items): int
    {
        $prep = collect($items)->sum(function (array $item) {
            return (int) ($item['prep_weight'] ?? 0);
        });

        return max(22, (int) round(($prep / max(count($items), 1)) + 18));
    }

    private function collectProductStockDemand(array $items): array
    {
        $demand = [];

        foreach ($items as $item) {
            $lineQty = max(1, (int) ($item['qty'] ?? 1));
            $type = (string) ($item['type'] ?? 'product');

            if ($type === 'product' && !empty($item['product_id'])) {
                $productId = (int) $item['product_id'];
                $demand[$productId] = ($demand[$productId] ?? 0) + $lineQty;
                continue;
            }

            if ($type === 'combo' && !empty($item['sub_items']) && is_array($item['sub_items'])) {
                foreach ($item['sub_items'] as $subItem) {
                    $productId = (int) ($subItem['product_id'] ?? 0);
                    $subQty = max(1, (int) ($subItem['qty'] ?? 1));
                    if ($productId > 0) {
                        $demand[$productId] = ($demand[$productId] ?? 0) + ($subQty * $lineQty);
                    }
                }
                continue;
            }

            if ($type === 'bundle' && !empty($item['bundle_items']) && is_array($item['bundle_items'])) {
                foreach ($item['bundle_items'] as $bundleItem) {
                    $productId = (int) ($bundleItem['product_id'] ?? 0);
                    $bundleQty = max(1, (int) ($bundleItem['qty'] ?? 1));
                    if ($productId > 0) {
                        $demand[$productId] = ($demand[$productId] ?? 0) + ($bundleQty * $lineQty);
                    }
                }
            }
        }

        return $demand;
    }

    private function reserveStockOrFail(array $demand): void
    {
        if (empty($demand)) {
            return;
        }

        $products = Product::query()
            ->whereIn('id', array_keys($demand))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($demand as $productId => $requiredQty) {
            $product = $products->get((int) $productId);
            if (!$product) {
                throw ValidationException::withMessages([
                    'items' => ["Producto #{$productId} no encontrado para reserva de stock."],
                ]);
            }

            if ((int) $product->stock_quantity < (int) $requiredQty) {
                throw ValidationException::withMessages([
                    'items' => ["Stock insuficiente para {$product->name}. Disponible: {$product->stock_quantity}, solicitado: {$requiredQty}."],
                ]);
            }
        }

        foreach ($demand as $productId => $requiredQty) {
            Product::query()
                ->where('id', (int) $productId)
                ->decrement('stock_quantity', (int) $requiredQty);
        }
    }

    private function createOrderItemSnapshots(Order $order, array $items): void
    {
        foreach ($items as $item) {
            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_id' => $item['type'] === 'product' ? ($item['product_id'] ?? null) : null,
                'combo_id' => $item['type'] === 'combo' ? ($item['combo_id'] ?? null) : null,
                'bundle_id' => $item['type'] === 'bundle' ? ($item['bundle_id'] ?? null) : null,
                'snapshot_name' => (string) ($item['name'] ?? 'Item'),
                'qty' => (int) ($item['qty'] ?? 1),
                'unit_base_price' => (float) ($item['unit_base_price'] ?? 0),
                'unit_extras_total' => (float) ($item['unit_extras_total'] ?? 0),
                'unit_price' => (float) ($item['unit_price'] ?? 0),
                'line_subtotal' => (float) ($item['line_subtotal'] ?? 0),
                'line_extras_total' => (float) ($item['line_extras_total'] ?? 0),
                'line_total' => (float) ($item['line_total'] ?? 0),
                'modifiers' => [
                    'type' => $item['type'] ?? 'product',
                    'excluded_ingredients' => $item['excluded_ingredients'] ?? [],
                    'extras' => $item['extras'] ?? [],
                    'sub_items' => $item['sub_items'] ?? [],
                    'bundle_items' => $item['bundle_items'] ?? [],
                    'bundle_snapshot' => $item['bundle_snapshot'] ?? null,
                ],
            ]);
        }
    }

    private function writeAudit(Request $request, string $action, string $entityType, ?int $entityId, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
        ]);
    }
}
