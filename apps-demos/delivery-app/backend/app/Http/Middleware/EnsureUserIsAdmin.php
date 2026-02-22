<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $roleName = $user?->roleModel?->name ?? $user?->role;

        if (!$user || $roleName !== 'admin') {
            return response()->json([
                'message' => 'Acceso denegado. Solo el perfil Admin puede modificar el catalogo.',
            ], 403);
        }

        return $next($request);
    }
}
