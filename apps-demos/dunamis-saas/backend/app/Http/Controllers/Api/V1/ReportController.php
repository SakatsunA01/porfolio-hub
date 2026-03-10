<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    public function profit(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $from = $request->query('from');
        $to = $request->query('to');
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));

        $paginator = Sale::query()
            ->with(['client', 'items'])
            ->where('organization_id', $organizationId)
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $rows = collect($paginator->items())->map(function (Sale $sale) {
            $costTotal = $sale->items->sum(fn ($item) => ($item->cost_ars ?? 0) * $item->quantity);
            $profit = (float) $sale->total_amount - $costTotal;
            $margin = $sale->total_amount > 0 ? ($profit / $sale->total_amount) * 100 : 0;

            return [
                'id' => $sale->id,
                'created_at' => $sale->created_at,
                'client' => $sale->client?->name ?? 'Consumidor Final',
                'total' => (float) $sale->total_amount,
                'cost_total' => round($costTotal, 2),
                'profit' => round($profit, 2),
                'margin' => round($margin, 2),
            ];
        });

        return $this->paginated($paginator, $rows);
    }
}
