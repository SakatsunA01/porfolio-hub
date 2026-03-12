<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantSlug = trim((string) ($request->header('X-Tenant-Slug') ?: $request->query('tenant_slug', '')));
        $organizationId = (int) ($request->header('X-Organization-Id') ?: $request->query('organization_id', 0));
        $commerceId = (int) ($request->header('X-Commerce-Id') ?: $request->query('commerce_id', 0));

        if ($tenantSlug !== '') {
            $request->attributes->set('tenant_slug', $tenantSlug);
        }

        if ($organizationId > 0) {
            $request->attributes->set('organization_id', $organizationId);
        }

        if ($commerceId > 0) {
            $request->attributes->set('commerce_id', $commerceId);
        }

        return $next($request);
    }
}
