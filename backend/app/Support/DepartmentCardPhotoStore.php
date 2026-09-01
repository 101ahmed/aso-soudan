<?php

namespace App\Support;

use App\Models\Department;
use App\Models\DepartmentCardPhoto;
use Illuminate\Http\UploadedFile;

class DepartmentCardPhotoStore
{
    public const ROLES = ['officer', 'deputy'];

    public static function store(Department $department, string $role, UploadedFile $file): void
    {
        self::assertRole($role);

        $bytes = self::readUploadedBytes($file);
        if ($bytes === '') {
            abort(500, 'Unable to store photo.');
        }

        DepartmentCardPhoto::query()->updateOrCreate(
            [
                'department_id' => $department->id,
                'role' => $role,
            ],
            [
                'mime' => self::detectMime($file, $bytes),
                'payload' => base64_encode($bytes),
            ]
        );

        $oldPath = $department->{"{$role}_photo_path"};
        $department->forceFill([
            "{$role}_photo_path" => "db:{$role}",
        ])->save();

        self::deleteDiskFile($oldPath);
    }

    public static function forget(Department $department, string $role): void
    {
        self::assertRole($role);

        DepartmentCardPhoto::query()
            ->where('department_id', $department->id)
            ->where('role', $role)
            ->delete();

        $oldPath = $department->{"{$role}_photo_path"};
        $department->forceFill([
            "{$role}_photo_path" => null,
        ])->save();

        self::deleteDiskFile($oldPath);
    }

    public static function has(Department $department, string $role): bool
    {
        self::assertRole($role);

        $path = (string) ($department->{"{$role}_photo_path"} ?? '');
        if ($path === '') {
            return false;
        }
        if (str_starts_with($path, 'db:')) {
            return true;
        }

        if (self::diskFile($path) !== null) {
            return true;
        }

        return DepartmentCardPhoto::query()
            ->where('department_id', $department->id)
            ->where('role', $role)
            ->exists();
    }

    public static function url(Department $department, string $role): ?string
    {
        if (! self::has($department, $role)) {
            return null;
        }

        $version = optional($department->updated_at)->getTimestamp() ?: time();

        return '/api/public/departments/'.$department->code.'/'.$role.'-photo?v='.$version;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    public static function contents(Department $department, string $role): ?array
    {
        self::assertRole($role);

        $row = DepartmentCardPhoto::query()
            ->where('department_id', $department->id)
            ->where('role', $role)
            ->first();

        if ($row && is_string($row->payload) && $row->payload !== '') {
            $decoded = base64_decode($row->payload, true);
            $bytes = ($decoded !== false && $decoded !== '') ? $decoded : $row->payload;
            if ($bytes !== '') {
                return [$bytes, $row->mime ?: 'image/jpeg'];
            }
        }

        $path = (string) ($department->{"{$role}_photo_path"} ?? '');
        $full = self::diskFile($path);
        if ($full !== null) {
            $bytes = @file_get_contents($full);
            if (is_string($bytes) && $bytes !== '') {
                return [$bytes, self::mimeFromPath($full)];
            }
        }

        return null;
    }

    public static function ingestFromDisk(Department $department): int
    {
        $ingested = 0;

        foreach (self::ROLES as $role) {
            $path = (string) ($department->{"{$role}_photo_path"} ?? '');
            if ($path === '' || str_starts_with($path, 'db:')) {
                continue;
            }

            $full = self::diskFile($path);
            if ($full === null) {
                $department->forceFill([
                    "{$role}_photo_path" => null,
                ])->save();
                continue;
            }

            $bytes = @file_get_contents($full);
            if (! is_string($bytes) || $bytes === '') {
                continue;
            }

            DepartmentCardPhoto::query()->updateOrCreate(
                [
                    'department_id' => $department->id,
                    'role' => $role,
                ],
                [
                    'mime' => self::mimeFromPath($full),
                    'payload' => base64_encode($bytes),
                ]
            );

            $department->forceFill([
                "{$role}_photo_path" => "db:{$role}",
            ])->save();

            $ingested++;
        }

        return $ingested;
    }

    private static function detectMime(UploadedFile $file, string $bytes): string
    {
        $ext = strtolower($file->getClientOriginalExtension()
            ?: pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION)
            ?: '');

        return self::mimeFromExtension($ext, $bytes);
    }

    private static function mimeFromPath(string $full): string
    {
        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));

        return self::mimeFromExtension($ext, (string) @file_get_contents($full));
    }

    private static function mimeFromExtension(string $ext, string $bytes): string
    {
        return match ($ext) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            default => str_starts_with($bytes, "\x89PNG") ? 'image/png'
                : (str_starts_with($bytes, 'GIF8') ? 'image/gif' : 'image/jpeg'),
        };
    }

    private static function diskFile(?string $path): ?string
    {
        if (! is_string($path) || $path === '' || str_starts_with($path, 'db:')
            || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        $full = storage_path('app/public/'.$relative);
        if (is_file($full)) {
            return $full;
        }

        $public = public_path('storage/'.$relative);

        return is_file($public) ? $public : null;
    }

    private static function deleteDiskFile(?string $path): void
    {
        $full = self::diskFile($path);
        if ($full !== null) {
            @unlink($full);
        }
    }

    private static function readUploadedBytes(UploadedFile $file): string
    {
        $candidates = array_filter([
            $file->getRealPath() ?: null,
            $file->getPathname() ?: null,
        ]);

        foreach ($candidates as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                $bytes = @file_get_contents($path);
                if (is_string($bytes) && $bytes !== '') {
                    return $bytes;
                }
            }
        }

        if (method_exists($file, 'getContent')) {
            $bytes = (string) $file->getContent();
            if ($bytes !== '') {
                return $bytes;
            }
        }

        return '';
    }

    private static function assertRole(string $role): void
    {
        if (! in_array($role, self::ROLES, true)) {
            abort(404);
        }
    }
}
