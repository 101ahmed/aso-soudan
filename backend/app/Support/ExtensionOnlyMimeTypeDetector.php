<?php

namespace App\Support;

use League\MimeTypeDetection\MimeTypeDetector;

/**
 * Mime detector that never instantiates ext-fileinfo (missing on some WAMP PHP builds).
 */
class ExtensionOnlyMimeTypeDetector implements MimeTypeDetector
{
    public function detectMimeType(string $path, $contents): ?string
    {
        return $this->detectMimeTypeFromPath($path);
    }

    public function detectMimeTypeFromBuffer(string $contents): ?string
    {
        return null;
    }

    public function detectMimeTypeFromPath(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            default => $extension === '' ? null : 'application/octet-stream',
        };
    }

    public function detectMimeTypeFromFile(string $path): ?string
    {
        return $this->detectMimeTypeFromPath($path);
    }
}
