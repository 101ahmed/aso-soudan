<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\DepartmentRoleMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('role_id'), function ($query) use ($request) {
                $query->whereHas('roles', fn ($q) => $q->where('roles.id', $request->integer('role_id')));
            })
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? [];
        unset($data['role_ids'], $data['password_confirmation']);

        $data['name'] = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));
        $data['locale'] = $data['locale'] ?? 'fr';
        $data['status'] = $data['status'] ?? 'active';

        $user = User::query()->create($data);
        $user->roles()->sync($roleIds);
        DepartmentRoleMap::syncUserDepartmentsFromRoles($user->fresh('roles'));

        return (new UserResource($user->load(['roles', 'departments'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load(['roles.permissions', 'departments']));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? null;
        unset($data['role_ids'], $data['password_confirmation']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (isset($data['first_name']) || isset($data['last_name'])) {
            $first = $data['first_name'] ?? $user->first_name;
            $last = $data['last_name'] ?? $user->last_name;
            $data['name'] = trim($first.' '.$last);
        }

        $user->update($data);

        if (is_array($roleIds)) {
            $user->roles()->sync($roleIds);
            DepartmentRoleMap::syncUserDepartmentsFromRoles($user->fresh('roles'));
        }

        return new UserResource($user->fresh()->load(['roles.permissions', 'departments']));
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->hasRole('SUPER_ADMIN') && User::query()->whereHas('roles', fn ($q) => $q->where('code', 'SUPER_ADMIN'))->count() <= 1) {
            return response()->json([
                'message' => 'Impossible de supprimer le dernier Super Admin.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'User deleted.',
        ]);
    }

    public function disable(User $user): UserResource
    {
        $user->update(['status' => 'inactive']);
        $user->tokens()->delete();

        return new UserResource($user->fresh()->load('roles'));
    }
}
