<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\User;
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

        $classes = $this->scopedClassGroups($request, $year)
            ->get(['id', 'subject_id', 'name', 'teacher_id']);
        $classIds = $classes->pluck('id');
        $subjectIds = $classes->pluck('subject_id')->unique()->filter()->values();

        $subjects = Subject::query()
            ->offered()
            ->when(
                $this->isTeacherOnly($request->user()) && $subjectIds->isNotEmpty(),
                fn ($q) => $q->whereIn('id', $subjectIds)
            )
            ->when(
                $this->isTeacherOnly($request->user()) && $subjectIds->isEmpty(),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->orderBy('name_ar')
            ->get(['id', 'code', 'name_ar', 'name_fr']);

        $sessions = $classIds->isEmpty()
            ? collect()
            : AcademicSession::query()
                ->whereIn('class_group_id', $classIds)
                ->get(['id', 'class_group_id', 'session_date', 'starts_at', 'status']);

        $attendance = $sessions->isEmpty()
            ? collect()
            : StudentAttendance::query()
                ->whereIn('academic_session_id', $sessions->pluck('id'))
                ->get(['academic_session_id', 'student_id', 'status']);

        $enrollments = $classIds->isEmpty()
            ? collect()
            : DB::table('class_students')
            ->join('students', 'students.id', '=', 'class_students.student_id')
            ->whereIn('class_students.class_group_id', $classIds)
            ->where('class_students.status', 'active')
            ->whereNull('students.deleted_at')
            ->select(
                'class_students.class_group_id',
                'students.id as student_id',
                'students.first_name',
                'students.last_name',
            )
            ->get();

        $classById = $classes->keyBy('id');
        $sessionById = $sessions->keyBy('id');

        $statusBySubject = [];
        $statusBySubjectStudent = [];
        foreach ($attendance as $row) {
            $session = $sessionById->get($row->academic_session_id);
            $class = $session ? $classById->get($session->class_group_id) : null;
            if (! $class) {
                continue;
            }
            $sid = (int) $class->subject_id;
            $status = (string) $row->status;
            $statusBySubject[$sid][$status] = ($statusBySubject[$sid][$status] ?? 0) + 1;
            $studentId = (int) $row->student_id;
            $statusBySubjectStudent[$sid][$studentId][$status] = ($statusBySubjectStudent[$sid][$studentId][$status] ?? 0) + 1;
        }

        $studentsBySubject = [];
        foreach ($enrollments as $row) {
            $class = $classById->get($row->class_group_id);
            if (! $class) {
                continue;
            }
            $sid = (int) $class->subject_id;
            $studentsBySubject[$sid][(int) $row->student_id] = [
                'id' => (int) $row->student_id,
                'full_name' => trim($row->first_name.' '.$row->last_name),
            ];
        }

        $payload = $subjects->map(function (Subject $subject) use (
            $classes,
            $sessions,
            $statusBySubject,
            $statusBySubjectStudent,
            $studentsBySubject,
        ) {
            $subjectClasses = $classes->where('subject_id', $subject->id);
            $subjectClassIds = $subjectClasses->pluck('id');
            $subjectSessions = $sessions->whereIn('class_group_id', $subjectClassIds);
            $counts = $statusBySubject[$subject->id] ?? [];
            $present = (int) ($counts[StudentAttendance::STATUS_PRESENT] ?? 0);
            $absent = (int) ($counts[StudentAttendance::STATUS_ABSENT] ?? 0);
            $late = (int) ($counts[StudentAttendance::STATUS_LATE] ?? 0);
            $excused = (int) ($counts[StudentAttendance::STATUS_EXCUSED] ?? 0);
            $recorded = $present + $absent + $late + $excused;
            $rate = $recorded > 0 ? round((($present + $late) / $recorded) * 100, 1) : null;

            $last = $subjectSessions->sortByDesc(function ($session) {
                return $session->session_date?->toDateString().' '.$session->starts_at;
            })->first();

            $students = collect($studentsBySubject[$subject->id] ?? [])
                ->map(function (array $student) use ($statusBySubjectStudent, $subject) {
                    $st = $statusBySubjectStudent[$subject->id][$student['id']] ?? [];
                    $present = (int) ($st[StudentAttendance::STATUS_PRESENT] ?? 0);
                    $absent = (int) ($st[StudentAttendance::STATUS_ABSENT] ?? 0);
                    $late = (int) ($st[StudentAttendance::STATUS_LATE] ?? 0);
                    $excused = (int) ($st[StudentAttendance::STATUS_EXCUSED] ?? 0);
                    $recorded = $present + $absent + $late + $excused;

                    return [
                        'id' => $student['id'],
                        'full_name' => $student['full_name'],
                        'present_count' => $present,
                        'absent_count' => $absent,
                        'late_count' => $late,
                        'excused_count' => $excused,
                        'recorded_count' => $recorded,
                        'attendance_rate' => $recorded > 0 ? round((($present + $late) / $recorded) * 100, 1) : null,
                    ];
                })
                ->sortByDesc('absent_count')
                ->values();

            return [
                'id' => $subject->id,
                'code' => $subject->code,
                'name_ar' => $subject->name_ar,
                'name_fr' => $subject->name_fr,
                'classes_count' => $subjectClasses->count(),
                'sessions_count' => $subjectSessions->count(),
                'students_count' => $students->count(),
                'present_count' => $present,
                'absent_count' => $absent,
                'late_count' => $late,
                'excused_count' => $excused,
                'recorded_count' => $recorded,
                'attendance_rate' => $rate,
                'last_session_date' => $last?->session_date?->toDateString(),
                'students' => $students,
            ];
        })->values();

        $totals = [
            'present_count' => $payload->sum('present_count'),
            'absent_count' => $payload->sum('absent_count'),
            'late_count' => $payload->sum('late_count'),
            'excused_count' => $payload->sum('excused_count'),
        ];
        $recorded = array_sum($totals);
        $totals['recorded_count'] = $recorded;
        $totals['attendance_rate'] = $recorded > 0
            ? round((($totals['present_count'] + $totals['late_count']) / $recorded) * 100, 1)
            : null;

        $studentsCount = $enrollments->pluck('student_id')->unique()->count();

        return response()->json([
            'academic_year' => $year,
            'students_count' => $studentsCount,
            'totals' => $totals,
            'generated_at' => now()->toIso8601String(),
            'subjects' => $payload,
        ]);
    }

    public function subjects(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        return response()->json([
            'data' => Subject::query()->offered()->orderBy('name_ar')->get(),
        ]);
    }

    public function classesBySubject(Request $request, Subject $subject): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');
        abort_if(Subject::isFrenchLanguage($subject), 404);

        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

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
                'status' => $att?->status,
                'recorded' => $att !== null,
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

    private function isTeacherOnly(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole('TEACHER')
            && ! $user->hasRole('SUPER_ADMIN')
            && ! $user->hasRole('ACADEMIC_SECRETARIAT');
    }

    private function scopedClassGroups(Request $request, ?AcademicYear $year)
    {
        $query = ClassGroup::query()->where('status', 'active');
        if ($year) {
            $query->where('academic_year_id', $year->id);
        }

        if (! $this->isTeacherOnly($request->user())) {
            return $query;
        }

        $request->user()->loadMissing('teacher.subjects');
        $teacherId = $request->user()->teacher?->id;
        $subjectIds = $request->user()->teacher?->subjects?->pluck('id') ?? collect();

        return $query->where(function ($inner) use ($teacherId, $subjectIds) {
            if ($teacherId) {
                $inner->where('teacher_id', $teacherId);
            }
            if ($subjectIds->isNotEmpty()) {
                $inner->orWhereIn('subject_id', $subjectIds);
            }
            if (! $teacherId && $subjectIds->isEmpty()) {
                $inner->whereRaw('1 = 0');
            }
        });
    }
}
