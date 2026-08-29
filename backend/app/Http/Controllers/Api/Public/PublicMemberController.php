<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class PublicMemberController extends Controller
{
    public function cities(): JsonResponse
    {
        return response()->json([
            'extra_cities' => Member::extraCityNames(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $key = 'member-register:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(Member::GENDERS)],
            'email' => ['required', 'email', 'max:190', Rule::unique('members', 'email')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', Rule::in(Member::allowedCities())],
            'membership_type' => ['nullable', Rule::in(Member::MEMBERSHIP_TYPES)],
        ]);

        RateLimiter::hit($key, 3600);

        $member = Member::query()->create([
            ...$data,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Membership request received.',
            'id' => $member->id,
        ], 201);
    }
}
