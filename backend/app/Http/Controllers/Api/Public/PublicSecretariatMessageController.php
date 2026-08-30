<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\SecretariatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class PublicSecretariatMessageController extends Controller
{
    public function store(Request $request, string $code): JsonResponse
    {
        $department = Department::query()->where('code', $code)->active()->firstOrFail();

        $key = 'secretariat-message:'.$code.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return response()->json([
                'message' => 'Too many messages. Please try again later.',
            ], 429);
        }

        $data = $request->validate([
            'sender_name' => ['required', 'string', 'max:190'],
            'sender_email' => ['required', 'email', 'max:190'],
            'sender_phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        RateLimiter::hit($key, 3600);

        $item = SecretariatMessage::query()->create([
            ...$data,
            'department_id' => $department->id,
            'status' => 'new',
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Message received.',
            'id' => $item->id,
        ], 201);
    }
}
