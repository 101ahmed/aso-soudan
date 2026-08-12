<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminAcademicAttendanceController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        $subjects = Subject::query()
            ->where('is_active', true)
            ->withCount([
                'classGroups as classes_count' => fn ($q) => $q->when(
                    $year,
                    fn ($qq) => $qq->where('academic_year_id', $year->id)
                )->where('status', 'active'),
            ])
            ->orderBy('name_fr')
            ->get()
            ->map(function (Subject $subject) use ($year) {
                $classIds = ClassGroup::query()
                    ->where('subject_id', $subject->id)
                    ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
                    ->pluck('id');

                $sessionIds = AcademicSession::query()
                    ->whereIn('class_group_id', $classIds)
                    ->pluck('id');

                $absences = StudentAttendance::query()
                    ->whereIn('academic_session_id', $sessionIds)
                    ->where('status', StudentAttendance::STATUS_ABSENT)
                    ->count();

                $presents = StudentAttendance::query()
                    ->whereIn('academic_session_id', $sessionIds)
                    ->where('status', StudentAttendance::STATUS_PRESENT)
                    ->count();

                return [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'name_ar' => $subject->name_ar,
                    'name_fr' => $subject->name_fr,
                    'classes_count' => $subject->classes_count,
                    'sessions_count' => $sessionIds->count(),
                    'present_count' => $presents,
                    'absent_count' => $absences,
                ];
            });

        return response()->json([
            'academic_year' => $year,
            'students_count' => Student::query()->where('status', 'active')->count(),
            'subjects' => $subjects,
        ]);
    }

    public function subjects(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        return response()->json([
            'data' => Subject::query()->where('is_active', true)->orderBy('name_fr')->get(),
        ]);
    }

    public function classesBySubject(Request $request, Subject $subject): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $year = AcademicYear::query()->current()->first();

        $classes = ClassGroup::query()
            ->with(['level', 'subject'])
            ->withCount(['students as students_count' => fn ($q) => $q->where('class_students.status', 'active')])
            ->where('subject_id', $subject->id)
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'subject' => $subject,
            'data' => $classes,
        ]);
    }

    public function sessionsIndex(Request $request, ClassGroup $classGroup): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $sessions = AcademicSession::query()
            ->where('class_group_id', $classGroup->id)
            ->withCount([
                'attendances as present_count' => fn ($q) => $q->where('status', 'present'),
                'attendances as absent_count' => fn ($q) => $q->where('status', 'absent'),
                'attendances as late_count' => fn ($q) => $q->where('status', 'late'),
                'attendances as excused_count' => fn ($q) => $q->where('status', 'excused'),
            ])
            ->latest('session_date')
            ->limit(40)
            ->get();

        return response()->json([
            'class_group' => $classGroup->load(['subject', 'level']),
            'data' => $sessions,
        ]);
    }

    public function sessionsStore(Request $request, ClassGroup $classGroup): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.create');

        $data = $request->validate([
            'session_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'room' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $session = AcademicSession::query()->create([
            ...$data,
            'starts_at' => $data['starts_at'].':00',
            'ends_at' => $data['ends_at'].':00',
            'class_group_id' => $classGroup->id,
            'status' => 'scheduled',
        ]);

        return response()->json(['data' => $session], 201);
    }

    public function sheet(Request $request, AcademicSession $session): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $session->load(['classGroup.subject', 'classGroup.level', 'attendances']);

        $students = $session->classGroup->students()
            ->wherePivot('status', 'active')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $byStudent = $session->attendances->keyBy('student_id');

        $rows = $students->map(function (Student $student) use ($byStudent) {
            $att = $byStudent->get($student->id);

            return [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'status' => $att?->status ?? StudentAttendance::STATUS_PRESENT,
                'notes' => $att?->notes,
                'attendance_id' => $att?->id,
            ];
        });

        return response()->json([
            'session' => [
                'id' => $session->id,
                'session_date' => $session->session_date?->toDateString(),
                'starts_at' => substr((string) $session->starts_at, 0, 5),
                'ends_at' => substr((string) $session->ends_at, 0, 5),
                'status' => $session->status,
                'room' => $session->room,
                'class_group' => $session->classGroup,
            ],
            'rows' => $rows,
            'statuses' => StudentAttendance::STATUSES,
        ]);
    }

    public function syncSheet(Request $request, AcademicSession $session): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.create');

        $data = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'rows.*.status' => ['required', Rule::in(StudentAttendance::STATUSES)],
            'rows.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data, $session, $request) {
            foreach ($data['rows'] as $row) {
                StudentAttendance::query()->updateOrCreate(
                    [
                        'academic_session_id' => $session->id,
                        'student_id' => $row['student_id'],
                    ],
                    [
                        'status' => $row['status'],
                        'notes' => $row['notes'] ?? null,
                        'recorded_by' => $request->user()->id,
                    ]
                );
            }

            $session->update(['status' => 'completed']);
        });

        return $this->sheet($request, $session->fresh());
    }

    public function studentReport(Request $request, Student $student): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $rows = StudentAttendance::query()
            ->with(['session.classGroup.subject'])
            ->where('student_id', $student->id)
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn (StudentAttendance $a) => [
                'id' => $a->id,
                'status' => $a->status,
                'notes' => $a->notes,
                'session_date' => $a->session?->session_date?->toDateString(),
                'subject' => $a->session?->classGroup?->subject,
                'class_name' => $a->session?->classGroup?->name,
            ]);

        $stats = [
            'present' => $rows->where('status', 'present')->count(),
            'absent' => $rows->where('status', 'absent')->count(),
            'late' => $rows->where('status', 'late')->count(),
            'excused' => $rows->where('status', 'excused')->count(),
        ];

        return response()->json([
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'status' => $student->status,
            ],
            'stats' => $stats,
            'data' => $rows,
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
