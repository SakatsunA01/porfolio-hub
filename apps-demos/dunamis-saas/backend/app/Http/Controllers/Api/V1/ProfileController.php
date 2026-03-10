<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\AuthUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        return $this->success(new AuthUserResource($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->fill($validated);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        return $this->success(new AuthUserResource($user->fresh()));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'errors' => [
                    'current_password' => ['La contrasena actual es incorrecta.'],
                ],
            ], 422);
        }

        $user->password = $validated['password'];
        $user->save();

        return $this->success([
            'message' => 'Contrasena actualizada correctamente.',
        ]);
    }
}
