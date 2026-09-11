<?php

namespace App\Support;

use App\Models\SportsTeam;
use Illuminate\Http\Request;

trait AssertsSportsSecretariat
{
    private function assertSports(string $code): void
    {
        abort_unless($code === 'sports', 404);
    }

    private function authorizeSport(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }

    private function boolish(Request $request, string $key, bool $default = false): bool
    {
        if (! $request->exists($key)) {
            return $default;
        }

        return filter_var($request->input($key), FILTER_VALIDATE_BOOLEAN);
    }

    private function optionalTeamId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        $id = (int) $value;
        if ($id < 1) {
            return null;
        }
        abort_unless(SportsTeam::query()->whereKey($id)->exists(), 422, 'Unknown team.');

        return $id;
    }
}
