<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function index(): JsonResponse
    {
        $query = AuditLog::query()
            ->with('user:id,name,email')
            ->latest('id')
            ->limit(300);
        $this->scopeQueryByTenant($query, $this->resolveTenantId());

        $logs = $query->get();

        return response()->json($logs);
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
