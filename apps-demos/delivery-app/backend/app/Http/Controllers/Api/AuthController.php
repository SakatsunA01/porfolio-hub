<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'tenant_slug' => ['nullable', 'string', 'max:120'],
        ]);

        $user = \App\Models\User::query()
            ->with(['roleModel', 'tenant'])
            ->where('email', $credentials['email'])
            ->when(
                !empty($credentials['tenant_slug']),
                fn ($query) => $query->where(function ($scopedQuery) use ($credentials) {
                    $scopedQuery
                        ->whereHas('tenant', fn ($tenantQuery) => $tenantQuery->where('slug', $credentials['tenant_slug']))
                        ->orWhere(function ($superadminQuery) {
                            $superadminQuery
                                ->whereNull('tenant_id')
                                ->where(function ($roleQuery) {
                                    $roleQuery
                                        ->where('role', 'superadmin')
                                        ->orWhereHas('roleModel', fn ($modelQuery) => $modelQuery->where('name', 'superadmin'));
                                });
                        });
                })
            )
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales invalidas.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Usuario inactivo.'], 403);
        }

        $token = $user->createToken('delivery-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'role' => $user->roleModel?->name ?? $user->role,
                'tenant_id' => $user->tenant_id,
                'tenant_slug' => $user->tenant?->slug,
                'tenant_name' => $user->tenant?->name,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roleModel', 'tenant']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'role' => $user->roleModel?->name ?? $user->role,
            'is_active' => $user->is_active,
            'tenant_id' => $user->tenant_id,
            'tenant_slug' => $user->tenant?->slug,
            'tenant_name' => $user->tenant?->name,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roleModel', 'tenant']);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'max:65535'],
        ]);

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'avatar_url' => $data['avatar_url'] ?? null,
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'role' => $user->roleModel?->name ?? $user->role,
            'is_active' => $user->is_active,
            'tenant_id' => $user->tenant_id,
            'tenant_slug' => $user->tenant?->slug,
            'tenant_name' => $user->tenant?->name,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada.',
        ]);
    }

    public function googleLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
            'tenant_slug' => ['nullable', 'string', 'max:120'],
        ]);

        $payload = $this->verifyGoogleIdToken($data['id_token']);
        if (!$payload || empty($payload['email'])) {
            return response()->json(['message' => 'Token de Google invalido.'], 401);
        }

        $tenantSlug = trim((string) ($data['tenant_slug'] ?? ''));
        $tenant = $this->resolveTenantBySlug($tenantSlug);
        $email = mb_strtolower(trim((string) $payload['email']));

        $user = User::query()->with(['roleModel', 'tenant'])->where('email', $email)->first();
        if ($user) {
            if ($tenant && $user->tenant_id && (int) $user->tenant_id !== (int) $tenant->id) {
                return response()->json(['message' => 'Este usuario pertenece a otro negocio.'], 403);
            }
            if (!$user->is_active) {
                return response()->json(['message' => 'Usuario inactivo.'], 403);
            }
        } else {
            if (!$tenant) {
                return response()->json(['message' => 'Negocio invalido para alta con Google.'], 422);
            }
            $clientRole = Role::query()->where('name', 'client')->first();
            if (!$clientRole) {
                return response()->json(['message' => 'Rol client no encontrado.'], 500);
            }

            $user = User::query()->create([
                'name' => trim((string) ($payload['name'] ?? 'Cliente Google')),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => 'client',
                'role_id' => $clientRole->id,
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ])->load(['roleModel', 'tenant']);
        }

        $token = $user->createToken('delivery-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'role' => $user->roleModel?->name ?? $user->role,
                'tenant_id' => $user->tenant_id,
                'tenant_slug' => $user->tenant?->slug,
                'tenant_name' => $user->tenant?->name,
            ],
        ]);
    }

    private function resolveTenantBySlug(string $tenantSlug): ?Tenant
    {
        if ($tenantSlug !== '') {
            return Tenant::query()
                ->where('slug', $tenantSlug)
                ->where('is_active', true)
                ->first();
        }

        return Tenant::query()->where('is_active', true)->orderBy('id')->first();
    }

    private function verifyGoogleIdToken(string $idToken): ?array
    {
        $response = Http::timeout(8)
            ->acceptJson()
            ->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

        if (!$response->ok()) {
            return null;
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return null;
        }

        $emailVerified = (string) ($payload['email_verified'] ?? '');
        if (!in_array($emailVerified, ['true', '1'], true)) {
            return null;
        }

        $configuredClientId = trim((string) env('GOOGLE_CLIENT_ID', ''));
        if ($configuredClientId !== '' && (string) ($payload['aud'] ?? '') !== $configuredClientId) {
            return null;
        }

        return $payload;
    }
}
