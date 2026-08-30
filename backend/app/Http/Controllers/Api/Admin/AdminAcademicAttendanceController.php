<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\ClassSchedule;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminAcademicAttendanceController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');

        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        $classes = $this->scopedClassGroups($request, $year)
            ->get(['id', 'subject_id', 'level_id', 'name', 'teacher_id']);
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
                'students.level_id',
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

        $levels = Level::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name_ar', 'name_fr']);

        $levelPayload = $this->summarizeByStudentLevel(
            $levels,
            $classes,
            $sessions,
            $attendance,
            $enrollments,
            $classById,
            $sessionById,
        );

        $pairStats = $this->summarizeBySubjectAndLevel(
            $classes,
            $sessions,
            $attendance,
            $enrollments,
            $classById,
            $sessionById,
        );

        $payload = $payload->map(function (array $subject) use ($levels, $pairStats, $classes) {
            $subject['levels'] = $levels->map(function ($level) use ($subject, $pairStats, $classes) {
                return $this->pairCell(
                    $pairStats,
                    $classes,
                    (int) $subject['id'],
                    (int) $level->id,
                    [
                        'id' => $level->id,
                        'code' => $level->code,
                        'name_ar' => $level->name_ar,
                        'name_fr' => $level->name_fr,
                    ],
                );
            })->values();

            return $subject;
        });

        $levelPayload = $levelPayload->map(function (array $level) use ($subjects, $pairStats, $classes) {
            $level['subjects'] = $subjects->map(function (Subject $subject) use ($level, $pairStats, $classes) {
                return $this->pairCell(
                    $pairStats,
                    $classes,
                    (int) $subject->id,
                    (int) $level['id'],
                    [
                        'id' => $subject->id,
                        'code' => $subject->code,
                        'name_ar' => $subject->name_ar,
                        'name_fr' => $subject->name_fr,
                    ],
                );
            })->values();

            return $level;
        });

        $studentsByClass = $enrollments->groupBy('class_group_id')->map->count();

        return response()->json([
            'academic_year' => $year,
            'students_count' => $studentsCount,
            'subjects_count' => $payload->count(),
            'classes_count' => $classes->count(),
            'totals' => $totals,
            'generated_at' => now()->toIso8601String(),
            'subjects' => $payload,
            'levels' => $levelPayload,
            'schedule' => Schema::hasTable('class_schedules')
                ? $this->weeklySchedule($classIds, $classes, $payload, $levels, $studentsByClass)
                : [],
            'upcoming_sessions' => $this->upcomingSessions($classIds, $classes, $payload, $levels, $studentsByClass, $sessions),
        ]);
    }

    public function subjects(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('attendance.view')
            || $request->user()?->hasPermission('teacher.view'),
            403
        );

        return response()->json([
            'data' => Subject::query()->offered()->orderBy('name_ar')->get(),
        ]);
    }

    public function levels(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('attendance.view')
            || $request->user()?->hasPermission('teacher.view')
            || $request->user()?->hasPermission('student.view'),
            403
        );

        return response()->json([
            'data' => Level::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name_ar', 'name_fr', 'sort_order']),
        ]);
    }

    public function classesBySubject(Request $request, Subject $subject): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');
        abort_if(Subject::isFrenchLanguage($subject), 404);

        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        $classes = $this->scopedClassGroups($request, $year)
            ->with(['level', 'subject'])
            ->withCount(['students as students_count' => fn ($q) => $q->where('class_students.status', 'active')])
            ->where('subject_id', $subject->id)
            ->orderBy('name')
            ->get();

        return response()->json([
            'subject' => $subject,
            'data' => $classes,
        ]);
    }

    public function classesByLevel(Request $request, Level $level): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');
        abort_unless($level->is_active, 404);

        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        $classes = $this->scopedClassGroups($request, $year)
            ->with(['level', 'subject'])
            ->withCount(['students as students_count' => fn ($q) => $q->where('class_students.status', 'active')])
            ->where('level_id', $level->id)
            ->orderBy('name')
            ->get()
            ->reject(fn (ClassGroup $class) => $class->subject && Subject::isFrenchLanguage($class->subject))
            ->values();

        return response()->json([
            'level' => $level->only(['id', 'code', 'name_ar', 'name_fr']),
            'data' => $classes,
        ]);
    }

    public function sessionsIndex(Request $request, ClassGroup $classGroup): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');
        $this->assertClassAccess($request, $classGroup);

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
        $this->assertClassAccess($request, $classGroup);

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
            'teacher_id' => $classGroup->teacher_id,
            'status' => 'scheduled',
        ]);

        return response()->json(['data' => $session], 201);
    }

    public function sheet(Request $request, AcademicSession $session): JsonResponse
    {
        $this->authorizePermission($request, 'attendance.view');
        $session->load(['classGroup.subject', 'classGroup.level', 'attendances']);
        abort_unless($session->classGroup, 404);
        $this->assertClassAccess($request, $session->classGroup);

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
        $session->loadMissing('classGroup');
        abort_unless($session->classGroup, 404);
        $this->assertClassAccess($request, $session->classGroup);

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

    private function assertClassAccess(Request $request, ClassGroup $classGroup): void
    {
        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        abort_unless(
            $this->scopedClassGroups($request, $year)->where('id', $classGroup->id)->exists(),
            403
        );
    }

    private function summarizeByStudentLevel(
        $levels,
        $classes,
        $sessions,
        $attendance,
        $enrollments,
        $classById,
        $sessionById,
    ) {
        $statusByLevel = [];
        $statusByLevelStudent = [];
        $studentLevel = $enrollments->keyBy('student_id')->map(fn ($row) => (int) $row->level_id);

        foreach ($attendance as $row) {
            $gid = (int) ($studentLevel[$row->student_id] ?? 0);
            if (! $gid) {
                $session = $sessionById->get($row->academic_session_id);
                $class = $session ? $classById->get($session->class_group_id) : null;
                $gid = (int) ($class?->level_id ?? 0);
            }
            if (! $gid) {
                continue;
            }
            $status = (string) $row->status;
            $statusByLevel[$gid][$status] = ($statusByLevel[$gid][$status] ?? 0) + 1;
            $studentId = (int) $row->student_id;
            $statusByLevelStudent[$gid][$studentId][$status] = ($statusByLevelStudent[$gid][$studentId][$status] ?? 0) + 1;
        }

        $studentsByLevel = [];
        foreach ($enrollments as $row) {
            $gid = (int) $row->level_id;
            if (! $gid) {
                continue;
            }
            $studentsByLevel[$gid][(int) $row->student_id] = [
                'id' => (int) $row->student_id,
                'full_name' => trim($row->first_name.' '.$row->last_name),
            ];
        }

        return $levels->map(function ($level) use (
            $classes,
            $sessions,
            $statusByLevel,
            $statusByLevelStudent,
            $studentsByLevel,
        ) {
            $gid = (int) $level->id;
            $groupClasses = $classes->where('level_id', $gid);
            $groupClassIds = $groupClasses->pluck('id');
            $groupSessions = $sessions->whereIn('class_group_id', $groupClassIds);
            $counts = $statusByLevel[$gid] ?? [];
            $present = (int) ($counts[StudentAttendance::STATUS_PRESENT] ?? 0);
            $absent = (int) ($counts[StudentAttendance::STATUS_ABSENT] ?? 0);
            $late = (int) ($counts[StudentAttendance::STATUS_LATE] ?? 0);
            $excused = (int) ($counts[StudentAttendance::STATUS_EXCUSED] ?? 0);
            $recorded = $present + $absent + $late + $excused;
            $rate = $recorded > 0 ? round((($present + $late) / $recorded) * 100, 1) : null;
            $last = $groupSessions->sortByDesc(function ($session) {
                return $session->session_date?->toDateString().' '.$session->starts_at;
            })->first();

            $students = collect($studentsByLevel[$gid] ?? [])
                ->map(function (array $student) use ($statusByLevelStudent, $gid) {
                    $st = $statusByLevelStudent[$gid][$student['id']] ?? [];
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
                'id' => $level->id,
                'code' => $level->code,
                'name_ar' => $level->name_ar,
                'name_fr' => $level->name_fr,
                'classes_count' => $groupClasses->count(),
                'sessions_count' => $groupSessions->count(),
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
    }

    private function summarizeBySubjectAndLevel(
        $classes,
        $sessions,
        $attendance,
        $enrollments,
        $classById,
        $sessionById,
    ): array {
        $statusByPair = [];
        foreach ($attendance as $row) {
            $session = $sessionById->get($row->academic_session_id);
            $class = $session ? $classById->get($session->class_group_id) : null;
            if (! $class?->subject_id || ! $class?->level_id) {
                continue;
            }
            $key = (int) $class->subject_id.':'.(int) $class->level_id;
            $status = (string) $row->status;
            $statusByPair[$key][$status] = ($statusByPair[$key][$status] ?? 0) + 1;
        }

        $result = [];
        foreach ($classes->groupBy(fn ($class) => (int) $class->subject_id.':'.(int) $class->level_id) as $key => $pairClasses) {
            $ids = $pairClasses->pluck('id');
            $counts = $statusByPair[$key] ?? [];
            $present = (int) ($counts[StudentAttendance::STATUS_PRESENT] ?? 0);
            $absent = (int) ($counts[StudentAttendance::STATUS_ABSENT] ?? 0);
            $late = (int) ($counts[StudentAttendance::STATUS_LATE] ?? 0);
            $excused = (int) ($counts[StudentAttendance::STATUS_EXCUSED] ?? 0);
            $recorded = $present + $absent + $late + $excused;
            $pairSessions = $sessions->whereIn('class_group_id', $ids);
            $last = $pairSessions->sortByDesc(function ($session) {
                return $session->session_date?->toDateString().' '.$session->starts_at;
            })->first();

            $result[$key] = [
                'present_count' => $present,
                'absent_count' => $absent,
                'late_count' => $late,
                'excused_count' => $excused,
                'recorded_count' => $recorded,
                'attendance_rate' => $recorded > 0
                    ? round((($present + $late) / $recorded) * 100, 1)
                    : null,
                'sessions_count' => $pairSessions->count(),
                'students_count' => $enrollments->whereIn('class_group_id', $ids)->pluck('student_id')->unique()->count(),
                'last_session_date' => $last?->session_date?->toDateString(),
            ];
        }

        return $result;
    }

    private function pairCell(array $pairStats, $classes, int $subjectId, int $levelId, array $names): array
    {
        $key = $subjectId.':'.$levelId;
        $stats = $pairStats[$key] ?? [
            'present_count' => 0,
            'absent_count' => 0,
            'late_count' => 0,
            'excused_count' => 0,
            'recorded_count' => 0,
            'attendance_rate' => null,
            'sessions_count' => 0,
            'students_count' => 0,
            'last_session_date' => null,
        ];
        $class = $classes->first(
            fn ($item) => (int) $item->subject_id === $subjectId && (int) $item->level_id === $levelId
        );

        return array_merge($names, $stats, [
            'class_group_id' => $class?->id,
        ]);
    }

    private function summarizeByClassKey(
        $groups,
        string $classKey,
        $classes,
        $sessions,
        $attendance,
        $enrollments,
        $classById,
        $sessionById,
    ) {
        $statusByGroup = [];
        $statusByGroupStudent = [];
        foreach ($attendance as $row) {
            $session = $sessionById->get($row->academic_session_id);
            $class = $session ? $classById->get($session->class_group_id) : null;
            if (! $class) {
                continue;
            }
            $gid = (int) $class->{$classKey};
            if (! $gid) {
                continue;
            }
            $status = (string) $row->status;
            $statusByGroup[$gid][$status] = ($statusByGroup[$gid][$status] ?? 0) + 1;
            $studentId = (int) $row->student_id;
            $statusByGroupStudent[$gid][$studentId][$status] = ($statusByGroupStudent[$gid][$studentId][$status] ?? 0) + 1;
        }

        $studentsByGroup = [];
        foreach ($enrollments as $row) {
            $class = $classById->get($row->class_group_id);
            if (! $class) {
                continue;
            }
            $gid = (int) $class->{$classKey};
            if (! $gid) {
                continue;
            }
            $studentsByGroup[$gid][(int) $row->student_id] = [
                'id' => (int) $row->student_id,
                'full_name' => trim($row->first_name.' '.$row->last_name),
            ];
        }

        return $groups->map(function ($group) use (
            $classKey,
            $classes,
            $sessions,
            $statusByGroup,
            $statusByGroupStudent,
            $studentsByGroup,
        ) {
            $gid = (int) $group->id;
            $groupClasses = $classes->where($classKey, $gid);
            $groupClassIds = $groupClasses->pluck('id');
            $groupSessions = $sessions->whereIn('class_group_id', $groupClassIds);
            $counts = $statusByGroup[$gid] ?? [];
            $present = (int) ($counts[StudentAttendance::STATUS_PRESENT] ?? 0);
            $absent = (int) ($counts[StudentAttendance::STATUS_ABSENT] ?? 0);
            $late = (int) ($counts[StudentAttendance::STATUS_LATE] ?? 0);
            $excused = (int) ($counts[StudentAttendance::STATUS_EXCUSED] ?? 0);
            $recorded = $present + $absent + $late + $excused;
            $rate = $recorded > 0 ? round((($present + $late) / $recorded) * 100, 1) : null;

            $last = $groupSessions->sortByDesc(function ($session) {
                return $session->session_date?->toDateString().' '.$session->starts_at;
            })->first();

            $students = collect($studentsByGroup[$gid] ?? [])
                ->map(function (array $student) use ($statusByGroupStudent, $gid) {
                    $st = $statusByGroupStudent[$gid][$student['id']] ?? [];
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
                'id' => $group->id,
                'code' => $group->code,
                'name_ar' => $group->name_ar,
                'name_fr' => $group->name_fr,
                'classes_count' => $groupClasses->count(),
                'sessions_count' => $groupSessions->count(),
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
    }

    private function weeklySchedule($classIds, $classes, $subjects, $levels, $studentsByClass)
    {
        if ($classIds->isEmpty()) {
            return [];
        }

        $subjectById = collect($subjects)->keyBy('id');
        $levelById = collect($levels)->keyBy('id');
        $classById = $classes->keyBy('id');

        return ClassSchedule::query()
            ->whereIn('class_group_id', $classIds)
            ->orderBy('weekday')
            ->orderBy('starts_at')
            ->get()
            ->map(function (ClassSchedule $slot) use ($classById, $subjectById, $levelById, $studentsByClass) {
                $class = $classById->get($slot->class_group_id);
                $subject = $class ? $subjectById->get($class->subject_id) : null;
                $level = $class ? $levelById->get($class->level_id) : null;

                return [
                    'id' => $slot->id,
                    'class_group_id' => $slot->class_group_id,
                    'class_name' => $class?->name,
                    'weekday' => (int) $slot->weekday,
                    'starts_at' => $slot->startsAt(),
                    'ends_at' => $slot->endsAt(),
                    'room' => $slot->room,
                    'students_count' => (int) ($studentsByClass[$slot->class_group_id] ?? 0),
                    'subject' => $subject ? [
                        'id' => $subject['id'] ?? $subject->id,
                        'name_ar' => $subject['name_ar'] ?? $subject->name_ar,
                        'name_fr' => $subject['name_fr'] ?? $subject->name_fr,
                    ] : null,
                    'level' => $level ? [
                        'id' => $level['id'] ?? $level->id,
                        'name_ar' => $level['name_ar'] ?? $level->name_ar,
                        'name_fr' => $level['name_fr'] ?? $level->name_fr,
                    ] : null,
                ];
            })
            ->values();
    }

    private function upcomingSessions($classIds, $classes, $subjects, $levels, $studentsByClass, $sessions)
    {
        if ($classIds->isEmpty()) {
            return [];
        }

        $subjectById = collect($subjects)->keyBy('id');
        $levelById = collect($levels)->keyBy('id');
        $classById = $classes->keyBy('id');
        $sessionIndex = $sessions->keyBy(function ($session) {
            return $session->class_group_id.'|'.$session->session_date?->toDateString().'|'.substr((string) $session->starts_at, 0, 5);
        });

        $upcoming = collect();
        $today = now()->startOfDay();

        $schedules = Schema::hasTable('class_schedules')
            ? ClassSchedule::query()
                ->whereIn('class_group_id', $classIds)
                ->orderBy('weekday')
                ->orderBy('starts_at')
                ->get()
            : collect();

        for ($offset = 0; $offset < 21 && $upcoming->count() < 10; $offset++) {
            $date = $today->copy()->addDays($offset);
            $weekday = (int) $date->isoWeekday();
            foreach ($schedules as $slot) {
                if ((int) $slot->weekday !== $weekday) {
                    continue;
                }
                $class = $classById->get($slot->class_group_id);
                $key = $slot->class_group_id.'|'.$date->toDateString().'|'.$slot->startsAt();
                $session = $sessionIndex->get($key);
                $subject = $class ? $subjectById->get($class->subject_id) : null;
                $level = $class ? $levelById->get($class->level_id) : null;
                $upcoming->push([
                    'session_id' => $session?->id,
                    'class_group_id' => $slot->class_group_id,
                    'class_name' => $class?->name,
                    'session_date' => $date->toDateString(),
                    'weekday' => $weekday,
                    'starts_at' => $slot->startsAt(),
                    'ends_at' => $slot->endsAt(),
                    'room' => $slot->room,
                    'status' => $session?->status ?? 'scheduled',
                    'students_count' => (int) ($studentsByClass[$slot->class_group_id] ?? 0),
                    'subject' => $subject ? [
                        'id' => $subject['id'] ?? $subject->id,
                        'name_ar' => $subject['name_ar'] ?? $subject->name_ar,
                        'name_fr' => $subject['name_fr'] ?? $subject->name_fr,
                    ] : null,
                    'level' => $level ? [
                        'id' => $level['id'] ?? $level->id,
                        'name_ar' => $level['name_ar'] ?? $level->name_ar,
                        'name_fr' => $level['name_fr'] ?? $level->name_fr,
                    ] : null,
                ]);
                if ($upcoming->count() >= 10) {
                    break;
                }
            }
        }

        if ($upcoming->isNotEmpty()) {
            return $upcoming->values();
        }

        return $sessions
            ->filter(function ($session) use ($today) {
                $date = $session->session_date?->startOfDay();

                return $date && $date->gte($today);
            })
            ->sortBy(fn ($session) => $session->session_date?->toDateString().' '.$session->starts_at)
            ->take(10)
            ->map(function ($session) use ($classById, $subjectById, $levelById, $studentsByClass) {
                $class = $classById->get($session->class_group_id);
                $subject = $class ? $subjectById->get($class->subject_id) : null;
                $level = $class ? $levelById->get($class->level_id) : null;

                return [
                    'session_id' => $session->id,
                    'class_group_id' => $session->class_group_id,
                    'class_name' => $class?->name,
                    'session_date' => $session->session_date?->toDateString(),
                    'weekday' => (int) ($session->session_date?->isoWeekday() ?? 0),
                    'starts_at' => substr((string) $session->starts_at, 0, 5),
                    'ends_at' => substr((string) $session->ends_at, 0, 5),
                    'room' => $session->room,
                    'status' => $session->status,
                    'students_count' => (int) ($studentsByClass[$session->class_group_id] ?? 0),
                    'subject' => $subject ? [
                        'id' => $subject['id'] ?? $subject->id,
                        'name_ar' => $subject['name_ar'] ?? $subject->name_ar,
                        'name_fr' => $subject['name_fr'] ?? $subject->name_fr,
                    ] : null,
                    'level' => $level ? [
                        'id' => $level['id'] ?? $level->id,
                        'name_ar' => $level['name_ar'] ?? $level->name_ar,
                        'name_fr' => $level['name_fr'] ?? $level->name_fr,
                    ] : null,
                ];
            })
            ->values();
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
