<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyStudentAttendance;
use App\Models\Level;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminTeacherRegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        $from = $request->date('from')?->toDateString() ?? now()->toDateString();
        $to = $request->date('to')?->toDateString() ?? $from;

        $students = Student::query()
            ->with('level')
            ->whereIn('status', ['active', 'pending'])
            ->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->integer('level_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $studentIds = $students->pluck('id');
        $byStudent = $studentIds->isEmpty()
            ? collect()
            : DailyStudentAttendance::query()
                ->whereBetween('attendance_date', [$from, $to])
                ->whereIn('student_id', $studentIds)
                ->get()
                ->groupBy('student_id');

        return response()->json([
            'from' => $from,
            'to' => $to,
            'levels' => Level::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name_ar', 'name_fr', 'sort_order']),
            'statuses' => DailyStudentAttendance::STATUSES,
            'students' => $students->map(function (Student $student) use ($byStudent) {
                $days = [];
                foreach ($byStudent->get($student->id, collect()) as $row) {
                    $days[$row->attendance_date->toDateString()] = [
                        'status' => $row->status,
                        'notes' => $row->notes,
                    ];
                }

                return [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'full_name' => $student->full_name,
                    'level_id' => $student->level_id,
                    'level' => $student->level?->only(['id', 'code', 'name_ar', 'name_fr']),
                    'days' => $days,
                ];
            })->values(),
        ]);
    }

    public function upsert(Request $request, Student $student): JsonResponse
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'attendance_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in(DailyStudentAttendance::STATUSES)],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($data['status'])) {
            DailyStudentAttendance::query()
                ->where('student_id', $student->id)
                ->whereDate('attendance_date', $data['attendance_date'])
                ->delete();
        } else {
            DailyStudentAttendance::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'attendance_date' => $data['attendance_date'],
                ],
                [
                    'status' => $data['status'],
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => $request->user()->id,
                ]
            );
        }

        return response()->json(['ok' => true]);
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user?->hasPermission('attendance.view')
            || $user?->hasRole('TEACHER')
            || $user?->hasRole('SUPER_ADMIN'),
            403
        );
    }

    private function authorizeManage(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user?->hasPermission('attendance.create')
            || $user?->hasPermission('attendance.update')
            || $user?->hasPermission('attendance.delete')
            || $user?->hasRole('TEACHER')
            || $user?->hasRole('SUPER_ADMIN'),
            403
        );
    }
}
