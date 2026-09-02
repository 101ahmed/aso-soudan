<?php

namespace App\Support;

use App\Models\Album;
use App\Models\Announcement;
use App\Models\CouncilMember;
use App\Models\Event;
use App\Models\ExternalDocument;
use App\Models\FinanceDocument;
use App\Models\Media;
use App\Models\MediaCenterItem;
use App\Models\News;
use App\Models\StoredFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StoredFileStore
{
    public const PREFIX = 'db:';

    /**
     * @var list<array{0: class-string<Model>, 1: string, 2: string}>
     */
    public const INGEST_SOURCES = [
        [News::class, 'image_path', 'news'],
        [Announcement::class, 'image_path', 'announcements'],
        [Event::class, 'image_path', 'events'],
        [Album::class, 'cover_path', 'albums'],
        [Media::class, 'path', 'album_media'],
        [MediaCenterItem::class, 'image_path', 'media_center'],
        [CouncilMember::class, 'photo_path', 'shura'],
        [ExternalDocument::class, 'file_path', 'external_documents'],
        [FinanceDocument::class, 'file_path', 'finance_documents'],
    ];

    /**
     * @return array{path: string, mime: string, size: int, original_name: ?string}
     */
    public static function store(UploadedFile $file, string $collection): array
    {
        $bytes = self::readUploadedBytes($file);
        if ($bytes === '') {
            abort(500, 'Unable to store file.');
        }

        $original = $file->getClientOriginalName() ?: null;
        $mime = self::detectMime($file, $bytes);

        return self::putBytes($bytes, $collection, $mime, $original);
    }

    /**
     * @return array{path: string, mime: string, size: int, original_name: ?string}
     */
    public static function replace(?string $oldPath, UploadedFile $file, string $collection): array
    {
        $stored = self::store($file, $collection);
        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $stored['path']) {
            self::forget($oldPath);
        }

        return $stored;
    }

    /**
     * @return array{path: string, mime: string, size: int, original_name: ?string}
     */
    public static function putBytes(
        string $bytes,
        string $collection,
        string $mime,
        ?string $originalName = null,
        ?string $legacyPath = null,
    ): array {
        $row = StoredFile::query()->create([
            'uuid' => (string) Str::uuid(),
            'collection' => $collection,
            'original_name' => $originalName,
            'mime' => $mime,
            'size' => strlen($bytes),
            'payload' => base64_encode($bytes),
            'legacy_path' => $legacyPath,
        ]);

        return [
            'path' => self::PREFIX.$row->uuid,
            'mime' => $row->mime,
            'size' => (int) $row->size,
            'original_name' => $row->original_name,
        ];
    }

    public static function forget(?string $path): void
    {
        $uuid = self::uuidFromPath($path);
        if ($uuid !== null) {
            StoredFile::query()->where('uuid', $uuid)->delete();
        }

        self::deleteDiskFile($path);
    }

    public static function uuidFromPath(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! preg_match('/^db:([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/i', $path, $matches)) {
            return null;
        }

        return strtolower($matches[1]);
    }

    public static function isStored(?string $path): bool
    {
        return self::uuidFromPath($path) !== null;
    }

    public static function url(?string $path): ?string
    {
        $uuid = self::uuidFromPath($path);
        if ($uuid === null) {
            return MediaUrl::absolute($path);
        }

        return '/api/public/files/'.$uuid;
    }

    /**
     * @return array{0: string, 1: string, 2: ?string}|null
     */
    public static function contentsByUuid(string $uuid): ?array
    {
        $row = StoredFile::query()->where('uuid', $uuid)->first();
        if (! $row || ! is_string($row->payload) || $row->payload === '') {
            return null;
        }

        $decoded = base64_decode($row->payload, true);
        $bytes = ($decoded !== false && $decoded !== '') ? $decoded : $row->payload;
        if ($bytes === '') {
            return null;
        }

        return [$bytes, $row->mime ?: 'application/octet-stream', $row->original_name];
    }

    /**
     * Copy leftover public-disk files into Postgres. Leaves DB text unchanged
     * when the file is already gone (typical after a Render redeploy).
     */
    public static function ingestAll(): int
    {
        $copied = 0;

        foreach (self::INGEST_SOURCES as [$class, $column, $collection]) {
            if (! class_exists($class)) {
                continue;
            }

            $instance = new $class;
            $table = $instance->getTable();
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $class::query()->orderBy('id')->each(function (Model $model) use ($column, $collection, &$copied) {
                $path = (string) ($model->{$column} ?? '');
                $next = self::ingestPath($path, $collection);
                if ($next !== null && $next !== $path) {
                    $model->forceFill([$column => $next])->save();
                    $copied++;
                }
            });
        }

        return $copied;
    }

    public static function ingestPath(?string $path, string $collection): ?string
    {
        if (! is_string($path) || $path === '') {
            return $path;
        }

        if (self::isStored($path)) {
            return $path;
        }

        $existing = StoredFile::query()->where('legacy_path', $path)->first();
        if ($existing) {
            return self::PREFIX.$existing->uuid;
        }

        $full = self::diskFile($path);
        if ($full === null) {
            return $path;
        }

        $bytes = @file_get_contents($full);
        if (! is_string($bytes) || $bytes === '') {
            return $path;
        }

        $stored = self::putBytes(
            $bytes,
            $collection,
            self::mimeFromPath($full),
            basename($full),
            $path,
        );

        return $stored['path'];
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
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => self::mimeFromMagic($bytes),
        };
    }

    private static function mimeFromMagic(string $bytes): string
    {
        if (str_starts_with($bytes, "\x89PNG")) {
            return 'image/png';
        }
        if (str_starts_with($bytes, 'GIF8')) {
            return 'image/gif';
        }
        if (str_starts_with($bytes, '%PDF')) {
            return 'application/pdf';
        }
        if (str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        return 'application/octet-stream';
    }

    private static function diskFile(?string $path): ?string
    {
        if (! is_string($path) || $path === '' || self::isStored($path)
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
}
