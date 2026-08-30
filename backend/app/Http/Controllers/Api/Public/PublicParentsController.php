<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouncilMeetingResource;
use App\Http\Resources\ParentSurveyResource;
use App\Models\CouncilMeeting;
use App\Models\ParentRegistration;
use App\Models\ParentSurvey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class PublicParentsController extends Controller
{
    public function meetings(): AnonymousResourceCollection
    {
        return CouncilMeetingResource::collection(
            CouncilMeeting::query()
                ->forCouncil('parents')
                ->publicVisible()
                ->where('status', '!=', 'cancelled')
                ->latest('scheduled_at')
                ->limit(12)
                ->get()
        );
    }

    public function surveys(): AnonymousResourceCollection
    {
        return ParentSurveyResource::collection(
            ParentSurvey::query()
                ->published()
                ->latest('published_at')
                ->latest('id')
                ->limit(12)
                ->get()
        );
    }

    public function storeRegistration(Request $request): JsonResponse
    {
        $key = 'parent-register:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'children' => ['required', 'array', 'min:1', 'max:12'],
            'children.*.first_name' => ['required', 'string', 'max:100'],
            'children.*.last_name' => ['required', 'string', 'max:100'],
            'children.*.birth_date' => ['nullable', 'date', 'before:today'],
            'children.*.gender' => ['nullable', Rule::in(['male', 'female'])],
            'children.*.level' => ['nullable', 'string', 'max:120'],
        ]);

        RateLimiter::hit($key, 3600);

        $item = ParentRegistration::query()->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        foreach ($data['children'] as $child) {
            $item->children()->create([
                'first_name' => $child['first_name'],
                'last_name' => $child['last_name'],
                'birth_date' => $child['birth_date'] ?? null,
                'gender' => $child['gender'] ?? null,
                'level' => $child['level'] ?? null,
            ]);
        }

        return response()->json([
            'message' => 'Registration received.',
            'id' => $item->id,
        ], 201);
    }

    public function storeSurveyResponse(Request $request, ParentSurvey $survey): JsonResponse
    {
        abort_unless($survey->status === 'published', 404);

        $questions = $survey->questionList();
        abort_unless(count($questions) > 0, 422, 'This survey is only available via an external form.');

        $key = 'parent-survey:'.$survey->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $data = $request->validate([
            'parent_name' => ['required', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['nullable', 'string', 'max:2000'],
        ]);

        RateLimiter::hit($key, 3600);

        $survey->responses()->create([
            'parent_name' => $data['parent_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'answers' => array_values($data['answers']),
        ]);

        return response()->json(['message' => 'Response received.'], 201);
    }
}
