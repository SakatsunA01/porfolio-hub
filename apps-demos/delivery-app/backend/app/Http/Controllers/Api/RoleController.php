<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Role::query()->orderBy('id')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'label' => ['required', 'string', 'max:255'],
        ]);

        $role = Role::query()->create($data);

        return response()->json($role, 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
        ]);

        $role->update($data);

        return response()->json($role);
    }

    public function destroy(Role $role): JsonResponse
    {
        if (in_array($role->name, ['superadmin', 'admin', 'employee', 'driver', 'client'], true)) {
            return response()->json([
                'message' => 'No se puede eliminar un rol base del sistema.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Rol eliminado correctamente.',
        ]);
    }
}
