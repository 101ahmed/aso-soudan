<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'locale',
        'status',
        'password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function belongsToDepartment(Department|string|int $department): bool
    {
        if ($this->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($department instanceof Department) {
            $id = $department->id;
            $code = $department->code;
        } elseif (is_numeric($department)) {
            $id = (int) $department;
            $code = null;
        } else {
            $id = null;
            $code = (string) $department;
        }

        $this->loadMissing('departments');

        return $this->departments->contains(function (Department $item) use ($id, $code) {
            return ($id && $item->id === $id) || ($code && $item->code === $code);
        });
    }

    public function canAccessDepartment(Department|string $department, bool $write = false): bool
    {
        if ($this->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($this->hasRole('PRESIDENT') && ! $write) {
            return true;
        }

        return $this->belongsToDepartment($department);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasRole(string $code): bool
    {
        return $this->roles->contains('code', $code);
    }

    public function hasPermission(string $code): bool
    {
        if ($this->hasRole('SUPER_ADMIN')) {
            return true;
        }

        return $this->roles
            ->loadMissing('permissions')
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains('code', $code);
    }

    public function permissionCodes(): array
    {
        if ($this->hasRole('SUPER_ADMIN')) {
            return Permission::query()->pluck('code')->all();
        }

        return $this->roles
            ->loadMissing('permissions')
            ->flatMap(fn (Role $role) => $role->permissions->pluck('code'))
            ->unique()
            ->values()
            ->all();
    }
}
