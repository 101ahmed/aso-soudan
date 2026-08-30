<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    public static function record(
        string $action,
        ?int $userId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        array $meta = [],
        ?Request $request = null,
    ): void {
        $request ??= request();

        AuditLog::query()->create([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? Str::limit($request->userAgent(), 512, '') : null,
            'meta' => $meta === [] ? null : $meta,
            'created_at' => now(),
        ]);
    }
}
