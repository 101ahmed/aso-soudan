<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $rolesLoadedWithPermissions = $this->relationLoaded('roles')
            && $this->roles->every(fn ($role) => $role->relationLoaded('permissions'));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'locale' => $this->locale,
            'status' => $this->status,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'departments' => $this->whenLoaded('departments', function () {
                return $this->departments->map(fn ($d) => [
                    'id' => $d->id,
                    'code' => $d->code,
                    'name_ar' => $d->name_ar,
                    'name_fr' => $d->name_fr,
                    'is_primary' => (bool) ($d->pivot?->is_primary),
                ])->values();
            }),
            'permissions' => $this->when($rolesLoadedWithPermissions, fn () => $this->permissionCodes()),
        ];
    }
}
