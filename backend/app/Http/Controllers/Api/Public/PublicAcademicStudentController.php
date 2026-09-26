<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\EducationStage;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class PublicAcademicStudentController extends Controller
{
    public function catalog(): JsonResponse
    {
        return response()->json([
            'stages' => EducationStage::query()
                ->where('is_active', true)
                ->with(['levels' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name_ar', 'name_fr', 'sort_order']),
            'subjects' => Subject::query()->offered()->orderBy('name_ar')->get(['id', 'code', 'name_ar', 'name_fr']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $key = 'student-register:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'guardian_name' => ['required', 'string', 'max:190'],
            'guardian_email' => ['required', 'email', 'max:190'],
            'guardian_phone' => ['nullable', 'string', 'max:50'],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'consent' => ['accepted'],
        ]);

        $level = Level::query()->where('is_active', true)->find($data['level_id']);
        if (! $level) {
            return response()->json([
                'message' => 'The selected level is invalid.',
                'errors' => ['level_id' => ['The selected level is invalid.']],
            ], 422);
        }

        $subjectIds = array_values(array_unique(array_map('intval', $data['subject_ids'] ?? [])));
        $offeredIds = Subject::query()->offered()->whereIn('id', $subjectIds)->pluck('id')->all();

        RateLimiter::hit($key, 3600);

        $student = DB::transaction(function () use ($data, $level, $offeredIds) {
            $yearId = AcademicYear::query()->current()->value('id')
                ?? AcademicYear::query()->orderByDesc('id')->value('id');

            $student = Student::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'] ?? null,
                'academic_year_id' => $yearId,
                'education_stage_id' => $level->education_stage_id,
                'level_id' => $level->id,
                'status' => 'active',
                'notes' => $this->guardianNotes($data),
                'registered_at' => now(),
            ]);

            $guardian = $this->upsertGuardian($data);
            $student->guardians()->syncWithoutDetaching([
                $guardian->id => [
                    'relationship' => 'parent',
                    'is_primary' => true,
                ],
            ]);

            if ($offeredIds !== []) {
                $sync = [];
                foreach ($offeredIds as $id) {
                    $sync[$id] = ['academic_year_id' => $yearId];
                }
                $student->subjects()->sync($sync);
            }

            $student->enrollInActiveLevelClasses($offeredIds);

            return $student;
        });

        return response()->json([
            'message' => 'Student registered.',
            'id' => $student->id,
        ], 201);
    }

    private function upsertGuardian(array $data): Guardian
    {
        [$first, $last] = $this->splitName($data['guardian_name']);
        $email = strtolower(trim($data['guardian_email']));

        $guardian = Guardian::query()->where('email', $email)->first();
        if ($guardian) {
            $guardian->update([
                'first_name' => $first,
                'last_name' => $last,
                'phone' => $data['guardian_phone'] ?: $guardian->phone,
                'status' => 'active',
            ]);

            return $guardian;
        }

        return Guardian::query()->create([
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => $data['guardian_phone'] ?: null,
            'status' => 'active',
        ]);
    }

    private function splitName(string $full): array
    {
        $full = trim(preg_replace('/\s+/u', ' ', $full) ?? $full);
        $parts = preg_split('/\s+/u', $full, 2) ?: [$full];

        return [$parts[0], $parts[1] ?? $parts[0]];
    }

    private function guardianNotes(array $data): string
    {
        return trim(implode(' | ', array_filter([
            'ولي الأمر: '.$data['guardian_name'],
            $data['guardian_email'],
            $data['guardian_phone'] ?? null,
        ])));
    }
}
