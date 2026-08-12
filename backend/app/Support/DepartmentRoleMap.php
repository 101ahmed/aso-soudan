<?php

namespace App\Support;

use App\Models\Department;
use App\Models\User;

class DepartmentRoleMap
{
    public const ROLE_TO_CODE = [
        'GENERAL_SECRETARIAT' => 'general',
        'ACADEMIC_SECRETARIAT' => 'academic',
        'SOCIAL_SECRETARIAT' => 'social',
        'MEDIA_SECRETARIAT' => 'media',
        'WOMEN_CHILDREN' => 'women-children',
        'STATISTICS_SECRETARIAT' => 'statistics',
        'EXTERNAL_RELATIONS' => 'external-relations',
        'SPORTS_SECRETARIAT' => 'sports',
        'SHURA_COUNCIL' => 'shura',
        'SHURA_PRESIDENT' => 'shura',
        'SHURA_VICE_PRESIDENT' => 'shura',
        'SHURA_SECRETARY' => 'shura',
        'SHURA_MEMBER' => 'shura',
        'SHURA_CONTENT_EDITOR' => 'shura',
    ];

    public static function codeForRole(string $roleCode): ?string
    {
        return self::ROLE_TO_CODE[$roleCode] ?? null;
    }

    public static function syncUserDepartmentsFromRoles(User $user): void
    {
        $user->loadMissing('roles');
        $codes = [];
        foreach ($user->roles as $role) {
            $code = self::codeForRole($role->code);
            if ($code) {
                $codes[] = $code;
            }
        }

        if ($codes === []) {
            return;
        }

        $ids = Department::query()->whereIn('code', $codes)->pluck('id');
        $sync = [];
        $first = true;
        foreach ($ids as $id) {
            $sync[$id] = ['is_primary' => $first];
            $first = false;
        }

        if ($sync !== []) {
            $user->departments()->syncWithoutDetaching($sync);
        }
    }
}
