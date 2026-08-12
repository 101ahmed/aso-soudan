<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

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

        $relative = Storage::disk($disk)->url($path);

        if (str_starts_with($relative, 'http://') || str_starts_with($relative, 'https://')) {
            return $relative;
        }

        return url($relative);
    }
}
