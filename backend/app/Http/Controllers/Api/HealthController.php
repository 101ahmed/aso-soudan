<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = 'ok';
        $adminExists = false;

        try {
            DB::connection()->getPdo();
            $email = getenv('ADMIN_EMAIL') ?: 'admin@acs-rennes.fr';
            $adminExists = User::query()->where('email', $email)->exists();
        } catch (\Throwable) {
            $database = 'error';
        }

        return response()->json([
            'status' => $database === 'ok' ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'env' => config('app.env'),
            'database' => $database,
            'admin_exists' => $adminExists,
            'locale' => config('app.locale'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
