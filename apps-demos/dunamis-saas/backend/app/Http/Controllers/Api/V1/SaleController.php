<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $perPage = max(1, min(50, (int) $request->query('per_page', 20)));

        $paginator = Sale::query()
            ->with(['user', 'client', 'items.product'])
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return $this->paginated($paginator, SaleResource::collection($paginator->items()));
    }

    public function show(Request $request, Sale $sale): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        abort_if((int) $sale->organization_id !== $organizationId, 404);

        $sale->load(['user', 'client', 'items.product']);

        return $this->success(new SaleResource($sale));
    }

    public function ticket(Request $request, Sale $sale): JsonResponse
    {
        return $this->show($request, $sale);
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $userId = (int) $request->user()->id;

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)),
            ],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($query) => $query->where('organization_id', $organizationId)),
            ],
            'moneda_cobro' => ['required', Rule::in(['ARS', 'USD'])],
            'exchange_rate_used' => ['nullable', 'numeric', 'min:0'],
        ]);

        $sale = DB::transaction(function () use ($data, $organizationId, $userId) {
            $total = collect($data['items'])->sum(fn ($item) => $item['cantidad'] * $item['precio_unitario']);

            $sale = Sale::query()->create([
                'organization_id' => $organizationId,
                'user_id' => $userId,
                'client_id' => $data['client_id'] ?? null,
                'total_amount' => $total,
                'payment_method' => 'cash',
                'status' => 'completed',
                'moneda_cobro' => $data['moneda_cobro'],
                'exchange_rate_used' => $data['moneda_cobro'] === 'USD'
                    ? ($data['exchange_rate_used'] ?? null)
                    : null,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::query()
                    ->where('organization_id', $organizationId)
                    ->where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = (int) $item['cantidad'];
                if ((int) $product->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Stock insuficiente para {$product->name}."],
                    ]);
                }

                $product->decrement('stock_quantity', $quantity);
                $costArs = $product->cost_ars ?? $product->cost_price ?? 0;

                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $item['precio_unitario'],
                    'cost_ars' => $costArs,
                    'total_price' => $quantity * $item['precio_unitario'],
                ]);
            }

            return $sale;
        });

        $sale->load(['user', 'client', 'items.product']);

        return $this->success(new SaleResource($sale), status: 201);
    }
}
