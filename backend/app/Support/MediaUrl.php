<?php

namespace App\Support;

class MediaUrl
{
    public static function absolute(?string $path, string $disk = 'public'): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $relative = ltrim(str_replace('\\', '/', $path), '/');
        $base = rtrim((string) config("filesystems.disks.{$disk}.url", ''), '/');

        if ($base === '') {
            $base = rtrim((string) config('app.url'), '/').'/storage';
        }

        return $base.'/'.$relative;
    }
}
