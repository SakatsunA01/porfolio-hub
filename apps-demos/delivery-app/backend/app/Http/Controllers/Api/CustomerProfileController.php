<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerProfileController extends Controller
{
    public function self(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $this->resolveTenantId();
        $profile = $this->findSelfProfile($request, $tenantId);

        return response()->json([
            'display_name' => $profile?->display_name ?: $user?->name,
            'email' => $user?->email,
            'phone' => $profile?->phone,
            'last_address' => $profile?->last_address,
            'apartment' => $profile?->apartment,
            'address_reference' => $profile?->address_reference,
            'delivery_notes' => $profile?->delivery_notes,
            'avatar_url' => $profile?->avatar_url,
        ]);
    }

    public function updateSelf(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $this->resolveTenantId();
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'last_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'apartment' => ['sometimes', 'nullable', 'string', 'max:120'],
            'address_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'delivery_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'max:65535'],
        ]);

        $profile = $this->findSelfProfile($request, $tenantId);
        if (!$profile) {
            $profile = new CustomerProfile();
        }

        $profile->fill([
            'tenant_id' => $tenantId > 0 ? $tenantId : null,
            'user_id' => $user?->id,
            'customer_key' => $this->selfCustomerKey($request),
            'display_name' => $data['display_name'],
            'phone' => $data['phone'] ?? null,
            'last_address' => $data['last_address'] ?? null,
            'apartment' => $data['apartment'] ?? null,
            'address_reference' => $data['address_reference'] ?? null,
            'delivery_notes' => $data['delivery_notes'] ?? null,
            'avatar_url' => $data['avatar_url'] ?? null,
        ]);
        $profile->save();

        if ($user && $user->name !== $data['display_name']) {
            $user->forceFill([
                'name' => $data['display_name'],
            ])->save();
        }

        return response()->json([
            'display_name' => $profile->display_name,
            'email' => $user?->email,
            'phone' => $profile->phone,
            'last_address' => $profile->last_address,
            'apartment' => $profile->apartment,
            'address_reference' => $profile->address_reference,
            'delivery_notes' => $profile->delivery_notes,
            'avatar_url' => $profile->avatar_url,
        ]);
    }

    public function index(): JsonResponse
    {
        $tenantId = $this->resolveTenantId();

        $profilesQuery = CustomerProfile::query();
        $this->scopeQueryByTenant($profilesQuery, $tenantId);
        $profiles = $profilesQuery
            ->get()
            ->keyBy('customer_key');

        $ordersQuery = Order::query()
            ->selectRaw("LOWER(TRIM(customer)) as customer_key")
            ->selectRaw('MAX(customer) as customer_name')
            ->selectRaw('MAX(address) as last_address')
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total) as total_spent')
            ->selectRaw('MAX(created_at) as last_order_at')
            ->groupBy(DB::raw('LOWER(TRIM(customer))'))
            ->orderByDesc('total_spent')
            ->limit(200);
        $this->scopeQueryByTenant($ordersQuery, $tenantId);

        $stats = $ordersQuery
            ->get()
            ->map(function ($row, int $index) use ($profiles) {
                $profile = $profiles->get($row->customer_key);

                return [
                    'customer_key' => $row->customer_key,
                    'customer_name' => $profile?->display_name ?: $row->customer_name,
                    'last_address' => $profile?->last_address ?: $row->last_address,
                    'phone' => $profile?->phone,
                    'apartment' => $profile?->apartment,
                    'address_reference' => $profile?->address_reference,
                    'delivery_notes' => $profile?->delivery_notes,
                    'avatar_url' => $profile?->avatar_url,
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
        $tenantId = $this->resolveTenantId();
        $data = $request->validate([
            'customer_key' => ['required', 'string', 'max:255'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'last_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'apartment' => ['sometimes', 'nullable', 'string', 'max:120'],
            'address_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'delivery_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'is_blocked' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $profile = CustomerProfile::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId > 0 ? $tenantId : null,
                'customer_key' => mb_strtolower(trim($data['customer_key'])),
            ],
            [
                'display_name' => $data['display_name'] ?? $data['customer_key'],
                'phone' => $data['phone'] ?? null,
                'last_address' => $data['last_address'] ?? null,
                'apartment' => $data['apartment'] ?? null,
                'address_reference' => $data['address_reference'] ?? null,
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'is_blocked' => $data['is_blocked'] ?? false,
                'notes' => $data['notes'] ?? null,
            ]
        );

        return response()->json($profile);
    }

    public function block(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId();
        $data = $request->validate([
            'customer_key' => ['required', 'string', 'max:255'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $profile = CustomerProfile::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId > 0 ? $tenantId : null,
                'customer_key' => mb_strtolower(trim($data['customer_key'])),
            ],
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
        $tenantId = $this->resolveTenantId();
        $data = $request->validate([
            'customer_key' => ['required', 'string', 'max:255'],
        ]);

        $profileQuery = CustomerProfile::query()
            ->where('customer_key', mb_strtolower(trim($data['customer_key'])));
        $this->scopeQueryByTenant($profileQuery, $tenantId);
        $profile = $profileQuery->first();

        if ($profile) {
            $profile->update([
                'is_blocked' => false,
            ]);
        }

        return response()->json([
            'message' => 'Cliente desbloqueado.',
        ]);
    }

    private function resolveTenantId(): int
    {
        $userTenantId = (int) (request()->user()?->tenant_id ?? 0);
        if ($userTenantId > 0) {
            return $userTenantId;
        }

        $tenantSlug = trim((string) (request()->query('tenant_slug') ?: request()->header('X-Tenant-Slug') ?: ''));
        if ($tenantSlug !== '') {
            $tenantBySlug = Tenant::query()
                ->where('slug', $tenantSlug)
                ->where('is_active', true)
                ->first();

            if ($tenantBySlug) {
                return (int) $tenantBySlug->id;
            }
        }

        $tenant = Tenant::query()->where('is_active', true)->orderBy('id')->first();
        return (int) ($tenant?->id ?? 0);
    }

    private function findSelfProfile(Request $request, int $tenantId): ?CustomerProfile
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        $profileQuery = CustomerProfile::query();
        $this->scopeQueryByTenant($profileQuery, $tenantId);

        return $profileQuery
            ->where(function ($query) use ($user, $request) {
                $query
                    ->where('user_id', $user->id)
                    ->orWhere('customer_key', $this->selfCustomerKey($request));
            })
            ->orderByDesc('id')
            ->first();
    }

    private function selfCustomerKey(Request $request): string
    {
        $user = $request->user();
        $fallback = trim((string) ($user?->name ?? 'cliente'));
        $base = trim((string) ($user?->email ?? $fallback));
        return mb_strtolower($base);
    }

    private function scopeQueryByTenant($query, int $tenantId): void
    {
        if ($tenantId <= 0) {
            return;
        }

        $query->where(function ($tenantQuery) use ($tenantId) {
            $tenantQuery->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
        });
    }
}
