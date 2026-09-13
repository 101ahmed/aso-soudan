<?php

namespace App\Services;

use App\Models\AcademicExam;
use App\Models\AcademicExamGrade;
use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Support\Collection;

class AcademicAchievementService
{
    public const PERIOD_ORDER = ['term1' => 1, 'term2' => 2, 'term3' => 3, 'annual' => 4];

    public function round(?float $value): ?float
    {
        return $value === null ? null : round($value, 2);
    }

    /**
     * @param  Collection<int, AcademicExam>  $exams
     * @param  Collection<int, AcademicExamGrade>  $grades
     */
    public function studentOverallAverage(int $studentId, Collection $exams, Collection $grades): ?float
    {
        $bySubject = [];
        foreach ($exams as $exam) {
            $grade = $grades->first(fn (AcademicExamGrade $row) => (int) $row->academic_exam_id === (int) $exam->id
                && (int) $row->student_id === $studentId);
            $percent = $grade?->percent((float) $exam->max_score);
            if ($percent === null) {
                continue;
            }
            $bySubject[(int) $exam->subject_id][] = $percent;
        }

        if ($bySubject === []) {
            return null;
        }

        $subjectAverages = array_map(
            fn (array $percents) => array_sum($percents) / count($percents),
            $bySubject
        );

        return $this->round(array_sum($subjectAverages) / count($subjectAverages));
    }

    public function subjectAverage(int $studentId, int $subjectId, Collection $exams, Collection $grades): ?float
    {
        $percents = [];
        foreach ($exams->where('subject_id', $subjectId) as $exam) {
            $grade = $grades->first(fn (AcademicExamGrade $row) => (int) $row->academic_exam_id === (int) $exam->id
                && (int) $row->student_id === $studentId);
            $percent = $grade?->percent((float) $exam->max_score);
            if ($percent !== null) {
                $percents[] = $percent;
            }
        }

        if ($percents === []) {
            return null;
        }

        return $this->round(array_sum($percents) / count($percents));
    }

    public function classAverage(Collection $studentAverages): ?float
    {
        $values = $studentAverages->filter(fn ($value) => $value !== null)->values();
        if ($values->isEmpty()) {
            return null;
        }

        return $this->round($values->avg());
    }

    public function previousPeriodKey(string $period, ?AcademicYear $year): array
    {
        $order = self::PERIOD_ORDER[$period] ?? 1;
        if ($order > 1) {
            $previous = array_search($order - 1, self::PERIOD_ORDER, true);

            return ['academic_year_id' => $year?->id, 'period' => $previous ?: 'term1'];
        }

        $previousYear = $year
            ? AcademicYear::query()->where('id', '<', $year->id)->orderByDesc('id')->first()
            : null;

        return ['academic_year_id' => $previousYear?->id, 'period' => 'annual'];
    }

    public function trend(?float $current, ?float $previous): ?string
    {
        if ($current === null || $previous === null) {
            return null;
        }
        if ($current > $previous) {
            return 'up';
        }
        if ($current < $previous) {
            return 'down';
        }

        return 'same';
    }

    public function levelStudents(int $levelId, ?int $yearId = null): Collection
    {
        return Student::query()
            ->where('level_id', $levelId)
            ->whereIn('status', ['active', 'pending'])
            ->when($yearId, fn ($q) => $q->where(function ($inner) use ($yearId) {
                $inner->where('academic_year_id', $yearId)->orWhereNull('academic_year_id');
            }))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
