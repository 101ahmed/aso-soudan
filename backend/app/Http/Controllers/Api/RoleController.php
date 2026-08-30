<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $roles = Role::query()
            ->withCount('permissions')
            ->when($request->boolean('with_permissions'), fn ($q) => $q->with('permissions'))
            ->orderBy('code')
            ->get();

        return RoleResource::collection($roles);
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('permissions'));
    }

    public function syncPermissions(Request $request, Role $role): RoleResource
    {
        $data = $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        $role->permissions()->sync($data['permission_ids']);

        AuditLogger::record('role.permissions_updated', $request->user()?->id, 'role', $role->id, [
            'code' => $role->code,
        ], $request);

        return new RoleResource($role->fresh()->load('permissions'));
    }
}
