<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Level;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAcademicTimetableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        $from = $request->date('from')?->toDateString() ?? now()->toDateString();

        $query = AcademicSession::query()
            ->with(['classGroup.subject', 'classGroup.level', 'classGroup.teacher', 'teacher'])
            ->whereDate('session_date', '>=', $from)
            ->when($year, fn ($q) => $q->whereHas(
                'classGroup',
                fn ($inner) => $inner->where('academic_year_id', $year->id)
            ))
            ->orderBy('session_date')
            ->orderBy('starts_at')
            ->limit(100);

        if ($this->isTeacherOnly($request->user())) {
            $teacherId = $request->user()->teacher?->id;
            $query->where(function ($inner) use ($teacherId, $request, $year) {
                $classIds = ClassGroup::query()
                    ->where('status', 'active')
                    ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
                    ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
                    ->pluck('id');
                $inner->whereIn('class_group_id', $classIds);
                if ($teacherId) {
                    $inner->orWhere('teacher_id', $teacherId);
                }
            });
        }

        return response()->json([
            'academic_year' => $year,
            'data' => $query->get()->map(fn (AcademicSession $session) => $this->row($session))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeWrite($request);
        $data = $this->validated($request);
        $session = $this->persist(null, $data);

        return response()->json(['data' => $this->row($session)], 201);
    }

    public function update(Request $request, AcademicSession $session): JsonResponse
    {
        $this->authorizeWrite($request);
        $data = $this->validated($request);
        $session = $this->persist($session, $data);

        return response()->json(['data' => $this->row($session)]);
    }

    public function destroy(Request $request, AcademicSession $session): JsonResponse
    {
        $this->authorizeWrite($request);
        $session->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function persist(?AcademicSession $session, array $data): AcademicSession
    {
        $class = $this->resolveClassGroup($data);

        $payload = [
            'class_group_id' => $class->id,
            'teacher_id' => $data['teacher_id'],
            'session_date' => $data['session_date'],
            'starts_at' => $this->normalizeTime($data['starts_at']),
            'ends_at' => $this->normalizeTime($data['ends_at']),
            'room' => $data['room'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $session?->status ?? 'scheduled',
        ];

        if ($session) {
            $session->update($payload);

            return $session->fresh()->load(['classGroup.subject', 'classGroup.level', 'classGroup.teacher', 'teacher']);
        }

        return AcademicSession::query()->create($payload)
            ->load(['classGroup.subject', 'classGroup.level', 'classGroup.teacher', 'teacher']);
    }

    private function resolveClassGroup(array $data): ClassGroup
    {
        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->firstOrFail();
        $subject = Subject::query()->findOrFail($data['subject_id']);
        abort_if(Subject::isFrenchLanguage($subject), 422, 'This subject is not offered.');
        $level = Level::query()->findOrFail($data['level_id']);
        $teacher = Teacher::query()->findOrFail($data['teacher_id']);

        return ClassGroup::query()->updateOrCreate(
            [
                'academic_year_id' => $year->id,
                'subject_id' => $subject->id,
                'level_id' => $level->id,
            ],
            [
                'name' => $subject->name_ar.' — '.$level->name_ar,
                'teacher_id' => $teacher->id,
                'code' => ($subject->code ?: 'S').'-'.($level->code ?: $level->id),
                'capacity' => 20,
                'status' => 'active',
            ]
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'session_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function row(AcademicSession $session): array
    {
        $class = $session->classGroup;
        $teacher = $session->teacher ?: $class?->teacher;
        $subject = $class?->subject;
        $level = $class?->level;

        return [
            'id' => $session->id,
            'class_group_id' => $session->class_group_id,
            'session_date' => $session->session_date?->toDateString(),
            'starts_at' => substr((string) $session->starts_at, 0, 5),
            'ends_at' => substr((string) $session->ends_at, 0, 5),
            'room' => $session->room,
            'notes' => $session->notes,
            'status' => $session->status,
            'teacher_id' => $teacher?->id,
            'subject_id' => $subject?->id,
            'level_id' => $level?->id,
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'full_name' => $teacher->full_name,
            ] : null,
            'subject' => $subject ? [
                'id' => $subject->id,
                'code' => $subject->code,
                'name_ar' => $subject->name_ar,
                'name_fr' => $subject->name_fr,
            ] : null,
            'level' => $level ? [
                'id' => $level->id,
                'code' => $level->code,
                'name_ar' => $level->name_ar,
                'name_fr' => $level->name_fr,
            ] : null,
        ];
    }

    private function normalizeTime(string $time): string
    {
        $time = substr($time, 0, 8);

        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function authorizeWrite(Request $request): void
    {
        abort_if($this->isTeacherOnly($request->user()), 403);
        abort_unless($request->user()?->hasPermission('attendance.create'), 403);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }

    private function isTeacherOnly(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('TEACHER')
            && ! $user->hasRole('SUPER_ADMIN')
            && ! $user->hasRole('ACADEMIC_SECRETARIAT');
    }
}
