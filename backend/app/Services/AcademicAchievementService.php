<?php

namespace App\Services;

use App\Models\AcademicExam;
use App\Models\AcademicExamGrade;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Support\Collection;

class AcademicAchievementService
{
    public const PERIOD_ORDER = ['term1' => 1, 'term2' => 2, 'term3' => 3, 'annual' => 4];

    public const CORE_SUBJECT_CODES = ['QURAN', 'QUR', 'AR', 'MATH'];

    public const SCALE = 100.0;

    public const PASS_SCORE = 50.0;

    public function coreSubjects(): Collection
    {
        $order = ['QURAN' => 1, 'QUR' => 1, 'AR' => 2, 'MATH' => 3];

        return Subject::query()
            ->offered()
            ->whereIn('code', self::CORE_SUBJECT_CODES)
            ->get(['id', 'code', 'name_ar', 'name_fr'])
            ->sortBy(fn (Subject $subject) => $order[$subject->code] ?? 99)
            ->values();
    }

    public function scaledScore(?AcademicExamGrade $grade, AcademicExam $exam, float $outOf = self::SCALE): ?float
    {
        if (! $grade || $grade->is_absent || $grade->score === null) {
            return null;
        }

        $max = (float) $exam->max_score;
        if ($max <= 0) {
            return null;
        }

        return $this->round(((float) $grade->score / $max) * $outOf);
    }

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
            $score = $this->scaledScore($grade, $exam);
            if ($score === null) {
                continue;
            }
            $bySubject[(int) $exam->subject_id][] = $score;
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
            $score = $this->scaledScore($grade, $exam);
            if ($score !== null) {
                $percents[] = $score;
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

    public function passThreshold(Collection $exams): float
    {
        $values = $exams
            ->map(fn (AcademicExam $exam) => (float) $exam->pass_score)
            ->filter(fn (float $value) => $value > 0)
            ->values();
        if ($values->isEmpty()) {
            return self::PASS_SCORE;
        }

        return $this->round($values->avg()) ?? self::PASS_SCORE;
    }

    public function subjectPassScore(Collection $exams, int $subjectId): float
    {
        $exam = $exams->where('subject_id', $subjectId)->sortByDesc('id')->first();

        return $exam ? (float) $exam->pass_score : self::PASS_SCORE;
    }

    /**
     * @param  Collection<int, AcademicExam>  $yearExams
     * @param  Collection<int, AcademicExamGrade>  $yearGrades
     * @return array{term1:?float,term2:?float,term3:?float,year:?float}
     */
    public function studentYearGrades(int $studentId, Collection $yearExams, Collection $yearGrades): array
    {
        $terms = [];
        foreach (['term1', 'term2', 'term3'] as $period) {
            $terms[$period] = $this->studentOverallAverage(
                $studentId,
                $yearExams->where('period', $period)->values(),
                $yearGrades
            );
        }

        $filled = array_values(array_filter($terms, fn ($value) => $value !== null));
        $terms['year'] = $filled === [] ? null : $this->round(array_sum($filled) / count($filled));

        return $terms;
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

    public function trendDelta(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null) {
            return null;
        }

        return $this->round($current - $previous);
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
