<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    public function tenants(): JsonResponse
    {
        $rows = Tenant::query()
            ->withCount([
                'users',
                'users as active_users_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderByDesc('id')
            ->get();

        return response()->json($rows);
    }

    public function storeTenant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'plan_key' => ['nullable', Rule::in(['takeaway', 'full', 'bi'])],
            'billing_status' => ['nullable', Rule::in(['paid', 'pending', 'overdue', 'trial'])],
            'monthly_fee_ars' => ['nullable', 'integer', 'min:0'],
            'next_billing_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner_password' => ['nullable', 'string', 'min:6'],
        ]);

        $tenant = DB::transaction(function () use ($data) {
            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'plan_key' => $data['plan_key'] ?? 'full',
                'billing_status' => $data['billing_status'] ?? 'paid',
                'monthly_fee_ars' => (int) ($data['monthly_fee_ars'] ?? 160000),
                'next_billing_at' => $data['next_billing_at'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if (!empty($data['owner_email'])) {
                $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
                User::query()->create([
                    'name' => $data['owner_name'] ?? 'Admin ' . $tenant->name,
                    'email' => $data['owner_email'],
                    'password' => Hash::make($data['owner_password'] ?? 'demo1234'),
                    'role_id' => $adminRole->id,
                    'role' => $adminRole->name,
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]);
            }

            return $tenant;
        });

        return response()->json($tenant->fresh(), 201);
    }

    public function updateTenant(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:120', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'plan_key' => ['sometimes', Rule::in(['takeaway', 'full', 'bi'])],
            'billing_status' => ['sometimes', Rule::in(['paid', 'pending', 'overdue', 'trial'])],
            'monthly_fee_ars' => ['sometimes', 'integer', 'min:0'],
            'next_billing_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $tenant->update($data);

        return response()->json($tenant->fresh());
    }

    public function tenantUsers(Tenant $tenant): JsonResponse
    {
        $users = User::query()
            ->with('roleModel:id,name,label')
            ->where('tenant_id', $tenant->id)
            ->orderBy('id')
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roleModel?->name ?? $user->role,
                    'role_label' => $user->roleModel?->label ?? $user->role,
                    'is_active' => (bool) $user->is_active,
                    'created_at' => $user->created_at,
                ];
            });

        return response()->json($users->values());
    }

    public function storeTenantUser(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $role = Role::query()->findOrFail((int) $data['role_id']);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $role->id,
            'role' => $role->name,
            'tenant_id' => $tenant->id,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role->name,
            'role_label' => $role->label,
            'is_active' => (bool) $user->is_active,
        ], 201);
    }
}

