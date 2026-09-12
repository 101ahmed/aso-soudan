<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassStaffAssignment;
use App\Models\ClassSupervisorVisit;
use App\Models\Level;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminAcademicClassStaffController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'teacher.view');

        $year = ClassStaffAssignment::currentYear();
        $assignments = $year
            ? ClassStaffAssignment::query()
                ->with(['supervisor', 'counselor'])
                ->where('academic_year_id', $year->id)
                ->get()
                ->keyBy('level_id')
            : collect();
        $visits = $year
            ? ClassSupervisorVisit::query()
                ->where('academic_year_id', $year->id)
                ->get()
                ->groupBy('level_id')
            : collect();

        $months = $year ? $this->monthsFor($year) : [];

        $levels = Level::query()
            ->where('is_active', true)
            ->with('stage')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (Level $level) use ($assignments, $visits) {
                $row = $assignments->get($level->id);

                return $this->levelPayload(
                    $level,
                    $row,
                    $visits->get($level->id, collect()),
                );
            });

        return response()->json([
            'academic_year' => $year?->only(['id', 'name', 'is_current']),
            'months' => $months,
            'levels' => $levels,
        ]);
    }

    public function update(Request $request, Level $level): JsonResponse
    {
        $this->authorizeWrite($request);

        $year = ClassStaffAssignment::currentYear();
        if (! $year) {
            return response()->json(['message' => 'No academic year is configured.'], 422);
        }

        $data = $request->validate([
            'supervisor_name' => ['nullable', 'string', 'max:150'],
            'counselor_name' => ['nullable', 'string', 'max:150'],
        ]);

        $assignment = ClassStaffAssignment::query()->firstOrNew([
            'academic_year_id' => $year->id,
            'level_id' => $level->id,
        ]);
        if ($request->exists('supervisor_name')) {
            $assignment->supervisor_name = trim((string) ($data['supervisor_name'] ?? '')) ?: null;
            $assignment->supervisor_teacher_id = null;
        }
        if ($request->exists('counselor_name')) {
            $assignment->counselor_name = trim((string) ($data['counselor_name'] ?? '')) ?: null;
            $assignment->counselor_teacher_id = null;
        }
        $assignment->save();

        $assignment->load(['supervisor', 'counselor', 'level.stage']);
        $level->setRelation('stage', $assignment->level?->stage ?? $level->stage);

        $visits = ClassSupervisorVisit::query()
            ->where('academic_year_id', $year->id)
            ->where('level_id', $level->id)
            ->get();

        return response()->json($this->levelPayload($level, $assignment, $visits));
    }

    public function upsertVisit(Request $request, Level $level): JsonResponse
    {
        $this->authorizeWrite($request);

        $year = ClassStaffAssignment::currentYear();
        if (! $year) {
            return response()->json(['message' => 'No academic year is configured.'], 422);
        }

        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'visited_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $monthStart = Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $this->assertMonthInYear($year, $monthStart);
        $visitedOn = Carbon::parse($data['visited_on']);
        if ($visitedOn->format('Y-m') !== $data['month']) {
            throw ValidationException::withMessages([
                'visited_on' => ['The visit date must fall in the selected month.'],
            ]);
        }

        $visit = ClassSupervisorVisit::query()->updateOrCreate(
            [
                'academic_year_id' => $year->id,
                'level_id' => $level->id,
                'visit_month' => $monthStart->toDateString(),
            ],
            [
                'visited_on' => $visitedOn->toDateString(),
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $request->user()?->id,
            ],
        );

        return response()->json([
            'month' => $monthStart->format('Y-m'),
            'visited_on' => $visit->visited_on?->toDateString(),
            'notes' => $visit->notes,
        ]);
    }

    public function destroyVisit(Request $request, Level $level, string $month): JsonResponse
    {
        $this->authorizeWrite($request);

        $year = ClassStaffAssignment::currentYear();
        if (! $year) {
            return response()->json(['message' => 'No academic year is configured.'], 422);
        }

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            return response()->json(['message' => 'Invalid month.'], 422);
        }

        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        ClassSupervisorVisit::query()
            ->where('academic_year_id', $year->id)
            ->where('level_id', $level->id)
            ->whereDate('visit_month', $monthStart->toDateString())
            ->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function levelPayload(Level $level, ?ClassStaffAssignment $row, $visits): array
    {
        $visitMap = collect($visits)->mapWithKeys(function (ClassSupervisorVisit $visit) {
            $key = $visit->visit_month?->format('Y-m');

            return $key ? [$key => [
                'visited_on' => $visit->visited_on?->toDateString(),
                'notes' => $visit->notes,
            ]] : [];
        });

        return [
            'id' => $level->id,
            'code' => $level->code,
            'name_ar' => $level->name_ar,
            'name_fr' => $level->name_fr,
            'education_stage_id' => $level->education_stage_id,
            'stage' => $level->stage?->only(['id', 'code', 'name_ar', 'name_fr']),
            'supervisor_name' => $row?->supervisorDisplayName(),
            'counselor_name' => $row?->counselorDisplayName(),
            'supervisor' => $this->namedPerson($row?->supervisorDisplayName()),
            'counselor' => $this->namedPerson($row?->counselorDisplayName()),
            'visits' => (object) $visitMap->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private function monthsFor(AcademicYear $year): array
    {
        $start = $year->starts_on->copy()->startOfMonth();
        $end = $year->ends_on->copy()->startOfMonth();
        $months = [];
        while ($start->lte($end)) {
            $months[] = $start->format('Y-m');
            $start->addMonth();
        }

        return $months;
    }

    private function assertMonthInYear(AcademicYear $year, Carbon $monthStart): void
    {
        $from = $year->starts_on->copy()->startOfMonth();
        $to = $year->ends_on->copy()->startOfMonth();
        if ($monthStart->lt($from) || $monthStart->gt($to)) {
            throw ValidationException::withMessages([
                'month' => ['The month is outside the academic year.'],
            ]);
        }
    }

    private function namedPerson(?string $name): ?array
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        return ['full_name' => $name];
    }

    private function authorizeWrite(Request $request): void
    {
        abort_unless(
            $request->user()?->hasPermission('teacher.update')
            || $request->user()?->hasPermission('teacher.create'),
            403
        );
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
