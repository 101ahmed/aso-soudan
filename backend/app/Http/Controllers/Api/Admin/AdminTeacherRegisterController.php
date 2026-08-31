<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyStudentAttendance;
use App\Models\Level;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AdminTeacherRegisterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeView($request);

        return response()->json($this->registerPayload($request));
    }

    public function pdf(Request $request): Response
    {
        $this->authorizeView($request);

        $locale = in_array($request->string('locale')->toString(), ['ar', 'fr', 'en'], true)
            ? $request->string('locale')->toString()
            : 'ar';
        $payload = $this->registerPayload($request);
        $days = $this->periodDays($payload['from'], $payload['to'], $locale);
        $levelName = $this->selectedLevelName($payload, $request, $locale);
        $html = view('reports.teacher-register', [
            'locale' => $locale,
            'payload' => $payload,
            'days' => $days,
            'levelName' => $levelName,
        ])->render();
        $filename = 'attendance-'.$payload['from'].'-'.$payload['to'].'.pdf';

        if (class_exists(\Dompdf\Dompdf::class)) {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="'.$filename.'.html"',
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

    private function registerPayload(Request $request): array
    {
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

        return [
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
        ];
    }

    private function periodDays(string $from, string $to, string $locale): array
    {
        $weekdays = [
            'ar' => [0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'],
            'fr' => [0 => 'Dim', 1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam'],
            'en' => [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'],
        ][$locale] ?? [];

        $days = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        while ($cursor->lte($end)) {
            $days[] = [
                'iso' => $cursor->toDateString(),
                'label' => ($weekdays[$cursor->dayOfWeek] ?? '').' '.$cursor->format('d/m'),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    private function selectedLevelName(array $payload, Request $request, string $locale): string
    {
        if (! $request->filled('level_id')) {
            return '';
        }

        $level = collect($payload['levels'])->firstWhere('id', $request->integer('level_id'));
        if (! $level) {
            return '';
        }

        $level = is_array($level) ? $level : $level->toArray();

        return $locale === 'ar'
            ? ($level['name_ar'] ?? $level['name_fr'] ?? '')
            : ($level['name_fr'] ?? $level['name_ar'] ?? '');
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
