<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyStudentAttendance;
use App\Models\Level;
use App\Models\Student;
use App\Support\ArabicPdfGlyphs;
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
        $html = ArabicPdfGlyphs::shapeHtml($html);
        $filename = 'attendance-'.$payload['from'].'-'.$payload['to'].'.pdf';

        if (class_exists(\Dompdf\Dompdf::class)) {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isFontSubsettingEnabled', true);
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

    public function rename(Request $request, Student $student): JsonResponse
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
        ]);

        $student->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);

        return response()->json([
            'id' => $student->id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'full_name' => $student->full_name,
            'level_id' => $student->level_id,
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
        $fromInput = $request->date('from')?->toDateString()
            ?? $this->sundayOfWeek(now())->toDateString();
        $toInput = $request->date('to')?->toDateString() ?? $fromInput;
        $sundayDays = $this->sundayDates($fromInput, $toInput, 'ar');
        $from = $sundayDays[0]['iso'];
        $to = $sundayDays[array_key_last($sundayDays)]['iso'];

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
                ->whereIn('attendance_date', array_column($sundayDays, 'iso'))
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

    private function sundayOfWeek(Carbon $date): Carbon
    {
        return $date->copy()->startOfDay()->startOfWeek(Carbon::SUNDAY);
    }

    private function sundayDates(string $from, string $to, string $locale): array
    {
        $weekdays = [
            'ar' => [0 => 'الأحد', 1 => 'الإثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'],
            'fr' => [0 => 'Dim', 1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam'],
            'en' => [0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'],
        ][$locale] ?? [];

        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        if ($end->lt($cursor)) {
            $end = $cursor->copy();
        }

        $days = [];
        while ($cursor->lte($end)) {
            if ($cursor->dayOfWeek === Carbon::SUNDAY) {
                $days[] = $this->formatSunday($cursor, $weekdays);
            }
            $cursor->addDay();
        }

        if ($days === []) {
            $days[] = $this->formatSunday($this->sundayOfWeek(Carbon::parse($from)), $weekdays);
        }

        return $days;
    }

    private function formatSunday(Carbon $date, array $weekdays): array
    {
        return [
            'iso' => $date->toDateString(),
            'weekday' => $weekdays[Carbon::SUNDAY] ?? '',
            'date' => $date->format('d/m'),
            'label' => ($weekdays[Carbon::SUNDAY] ?? '').' '.$date->format('d/m'),
        ];
    }

    private function periodDays(string $from, string $to, string $locale): array
    {
        return $this->sundayDates($from, $to, $locale);
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
