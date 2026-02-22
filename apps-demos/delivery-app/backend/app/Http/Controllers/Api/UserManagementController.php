<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->with('roleModel')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user) => $this->mapUser($user));

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $role = Role::query()->findOrFail((int) $data['role_id']);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $role->id,
            'role' => $role->name,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->writeAudit($request, 'user.created', 'user', $user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ]);

        return response()->json($this->mapUser($user->load('roleModel')), 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['sometimes', 'required', 'integer', Rule::exists('roles', 'id')],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['role_id'])) {
            $role = Role::query()->findOrFail((int) $data['role_id']);
            $data['role'] = $role->name;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $before = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
        ];

        $user->update($data);
        $this->writeAudit($request, 'user.updated', 'user', $user->id, [
            'before' => $before,
            'after' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ],
        ]);

        return response()->json($this->mapUser($user->load('roleModel')));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $snapshot = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
        $user->tokens()->delete();
        $user->delete();
        $this->writeAudit($request, 'user.deleted', 'user', $user->id, $snapshot);

        return response()->json([
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    private function mapUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'role' => $user->roleModel?->name ?? $user->role,
            'role_label' => $user->roleModel?->label ?? $user->role,
            'is_active' => $user->is_active,
        ];
    }

    private function writeAudit(Request $request, string $action, string $entityType, ?int $entityId, array $metadata = []): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
        ]);
    }
}
