<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerProfileController extends Controller
{
    public function index(): JsonResponse
    {
        $profiles = CustomerProfile::query()
            ->get()
            ->keyBy('customer_key');

        $stats = Order::query()
            ->selectRaw("LOWER(TRIM(customer)) as customer_key")
            ->selectRaw('MAX(customer) as customer_name')
            ->selectRaw('MAX(address) as last_address')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total) as total_spent')
            ->selectRaw('MAX(created_at) as last_order_at')
            ->groupBy(DB::raw('LOWER(TRIM(customer))'))
            ->orderByDesc('total_spent')
            ->limit(200)
            ->get()
            ->map(function ($row, int $index) use ($profiles) {
                $profile = $profiles->get($row->customer_key);

                return [
                    'customer_key' => $row->customer_key,
                    'customer_name' => $profile?->display_name ?: $row->customer_name,
                    'last_address' => $profile?->last_address ?: $row->last_address,
                    'total_orders' => (int) $row->total_orders,
                    'total_spent' => (float) $row->total_spent,
                    'last_order_at' => $row->last_order_at,
                    'is_blocked' => (bool) ($profile?->is_blocked ?? false),
                    'notes' => $profile?->notes,
                    'rank' => $index + 1,
                ];
            })
            ->values();

        return response()->json($stats);
    }

    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_key' => ['required', 'string', 'max:255'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_blocked' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $profile = CustomerProfile::query()->updateOrCreate(
            ['customer_key' => mb_strtolower(trim($data['customer_key']))],
            [
                'display_name' => $data['display_name'] ?? $data['customer_key'],
                'last_address' => $data['last_address'] ?? null,
                'is_blocked' => $data['is_blocked'] ?? false,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return response()->json($profile);
    }

    public function block(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_key' => ['required', 'string', 'max:255'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $profile = CustomerProfile::query()->updateOrCreate(
            ['customer_key' => mb_strtolower(trim($data['customer_key']))],
            [
                'display_name' => $data['display_name'] ?? $data['customer_key'],
                'is_blocked' => true,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return response()->json($profile);
    }

    public function unblock(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_key' => ['required', 'string', 'max:255'],
        ]);

        $profile = CustomerProfile::query()
            ->where('customer_key', mb_strtolower(trim($data['customer_key'])))
            ->first();

        if ($profile) {
            $profile->update([
                'is_blocked' => false,
            ]);
        }

        return response()->json([
            'message' => 'Cliente desbloqueado.',
        ]);
    }
}
