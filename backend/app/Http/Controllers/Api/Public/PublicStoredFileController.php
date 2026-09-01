<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Support\StoredFileStore;
use Illuminate\Http\Response;

class PublicStoredFileController extends Controller
{
    public function show(string $uuid): Response
    {
        $contents = StoredFileStore::contentsByUuid($uuid);
        abort_if($contents === null, 404);

        [$bytes, $mime, $originalName] = $contents;
        $ext = match ($mime) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/jpeg' => 'jpg',
            'application/pdf' => 'pdf',
            default => strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION) ?: 'bin'),
        };

        $filename = is_string($originalName) && $originalName !== ''
            ? $originalName
            : 'file.'.$ext;
        $safe = preg_replace('/[\r\n"]+/', '_', $filename) ?: ('file.'.$ext);

        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) strlen($bytes),
            'Content-Disposition' => 'inline; filename="'.$safe.'"',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
