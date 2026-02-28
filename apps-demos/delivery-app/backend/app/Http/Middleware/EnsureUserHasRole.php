<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $roleName = $user?->roleModel?->name ?? $user?->role;

        if (!$user || !in_array($roleName, $roles, true)) {
            return response()->json([
                'message' => 'Acceso denegado para este perfil.',
            ], 403);
        }

        return $next($request);
    }
}

