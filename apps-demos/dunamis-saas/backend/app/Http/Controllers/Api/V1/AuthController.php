<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\AuthUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials, false)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales invalidas.'],
            ]);
        }

        $request->session()->regenerate();

        return $this->success(new AuthUserResource($request->user()));
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new AuthUserResource($request->user()));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }
}
