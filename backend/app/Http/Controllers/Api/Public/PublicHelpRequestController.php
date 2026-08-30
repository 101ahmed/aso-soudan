<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\HelpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class PublicHelpRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $key = 'help-request:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:190'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'city' => ['nullable', 'string', 'max:120'],
            'help_type' => ['required', Rule::in(HelpRequest::TYPES)],
            'details' => ['required', 'string', 'max:4000'],
            'family_size' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        RateLimiter::hit($key, 3600);

        $item = HelpRequest::query()->create([
            ...$data,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Help request received.',
            'reference' => $item->reference,
        ], 201);
    }
}
