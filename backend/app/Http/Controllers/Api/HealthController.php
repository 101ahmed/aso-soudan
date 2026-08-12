<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = 'ok';
        $adminExists = false;
        $driver = config('database.default');

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
            'db_driver' => $driver,
            'admin_exists' => $adminExists,
            'has_tokens_table' => $database === 'ok' && Schema::hasTable('personal_access_tokens'),
            'mail_mailer' => config('mail.default'),
            'frontend_url' => config('app.frontend_url'),
            'locale' => config('app.locale'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
