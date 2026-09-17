<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicExamResource;
use App\Models\AcademicExam;
use App\Models\AcademicExamGrade;
use App\Models\AcademicStudentPeriodStat;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\AcademicAchievementService;
use App\Support\ArabicPdfGlyphs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class AdminAcademicExamController extends Controller
{
    public function __construct(private readonly AcademicAchievementService $achievement)
    {
    }

    public function catalog(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'exam.view');

        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        [$subjectIds, $levelIds] = $this->teacherScope($request->user());

        return response()->json([
            'academic_years' => AcademicYear::query()->orderByDesc('id')->get(['id', 'name', 'is_current']),
            'current_year' => $year?->only(['id', 'name', 'is_current']),
            'levels' => Level::query()
                ->where('is_active', true)
                ->when($levelIds !== null, fn ($q) => $q->whereIn('id', $levelIds ?: [-1]))
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name_ar', 'name_fr']),
            'subjects' => Subject::query()
                ->offered()
                ->when($subjectIds !== null, fn ($q) => $q->whereIn('id', $subjectIds ?: [-1]))
                ->orderBy('name_ar')
                ->get(['id', 'code', 'name_ar', 'name_fr']),
            'periods' => AcademicExam::PERIODS,
            'can_create' => $this->canManageExams($request->user()),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'exam.view');

        $filters = $request->only(['academic_year_id', 'level_id', 'subject_id', 'period', 'search']);
        $query = AcademicExam::query()
            ->with(['academicYear', 'level', 'subject'])
            ->withCount('grades')
            ->filtered($filters)
            ->latest('exam_date')
            ->latest('id');

        $this->restrictExamQuery($query, $request->user());

        $user = $request->user();
        $items = $query->paginate($request->integer('per_page', 30));
        $items->getCollection()->transform(function (AcademicExam $exam) use ($user) {
            $exam->can_grade = $this->canGradeExam($user, $exam);
            $exam->can_manage = $this->canManageExam($user, $exam);

            return $exam;
        });

        return AcademicExamResource::collection($items);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'exam.create');
        $data = $this->validatedExam($request);
        $this->assertTeacherCanAccess($request->user(), (int) $data['subject_id'], (int) $data['level_id']);

        $data['created_by'] = $request->user()?->id;
        $exam = AcademicExam::query()->create($data)->load(['academicYear', 'level', 'subject']);
        $exam->can_grade = true;
        $exam->can_manage = true;

        return (new AcademicExamResource($exam))->response()->setStatusCode(201);
    }

    public function show(Request $request, AcademicExam $exam): JsonResponse
    {
        $this->authorizePermission($request, 'exam.view');
        $this->assertCanViewExam($request->user(), $exam);

        $exam->load(['academicYear', 'level', 'subject']);
        $students = $this->achievement->levelStudents((int) $exam->level_id, (int) $exam->academic_year_id);
        $grades = $exam->grades()->get()->keyBy('student_id');

        $roster = $students->map(function (Student $student) use ($exam, $grades) {
            $grade = $grades->get($student->id);

            return [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'score' => $grade?->score !== null ? (float) $grade->score : null,
                'is_absent' => (bool) ($grade?->is_absent ?? false),
                'percent' => $grade?->percent((float) $exam->max_score),
                'passed' => $grade?->passed((float) $exam->pass_score),
            ];
        })->values();

        $entered = $roster->filter(fn ($row) => $row['score'] !== null || $row['is_absent'])->count();
        $presentScores = $roster->pluck('percent')->filter(fn ($v) => $v !== null);

        return response()->json([
            'exam' => (new AcademicExamResource($exam))->toArray($request) + [
                'can_grade' => $this->canGradeExam($request->user(), $exam),
                'can_manage' => $this->canManageExam($request->user(), $exam),
            ],
            'roster' => $roster,
            'stats' => [
                'students' => $roster->count(),
                'entered' => $entered,
                'average' => $presentScores->isEmpty() ? null : round($presentScores->avg(), 2),
                'pass_count' => $roster->where('passed', true)->count(),
            ],
        ]);
    }

    public function update(Request $request, AcademicExam $exam): AcademicExamResource
    {
        $this->authorizePermission($request, 'exam.update');
        abort_unless($this->canManageExam($request->user(), $exam), 403);
        $data = $this->validatedExam($request, $exam);
        $this->assertTeacherCanAccess(
            $request->user(),
            (int) ($data['subject_id'] ?? $exam->subject_id),
            (int) ($data['level_id'] ?? $exam->level_id)
        );
        $exam->update($data);
        $exam = $exam->fresh()->load(['academicYear', 'level', 'subject']);
        $exam->can_grade = $this->canGradeExam($request->user(), $exam);
        $exam->can_manage = true;

        return new AcademicExamResource($exam);
    }

    public function destroy(Request $request, AcademicExam $exam): JsonResponse
    {
        $this->authorizePermission($request, 'exam.delete');
        abort_unless($this->canManageExam($request->user(), $exam), 403);
        $exam->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function syncGrades(Request $request, AcademicExam $exam): JsonResponse
    {
        abort_unless(
            $request->user()?->hasPermission('exam.grade') || $request->user()?->hasPermission('exam.update'),
            403
        );
        abort_unless($this->canGradeExam($request->user(), $exam), 403);

        $payload = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'grades.*.score' => ['nullable', 'numeric', 'min:0'],
            'grades.*.is_absent' => ['sometimes', 'boolean'],
        ]);

        $max = (float) $exam->max_score;
        foreach ($payload['grades'] as $row) {
            $score = array_key_exists('score', $row) && $row['score'] !== null && $row['score'] !== ''
                ? (float) $row['score']
                : null;
            abort_if($score !== null && $score > $max, 422, 'Score exceeds max.');

            $absent = (bool) ($row['is_absent'] ?? false);
            $studentId = (int) $row['student_id'];

            if ($score === null && ! $absent) {
                AcademicExamGrade::query()
                    ->where('academic_exam_id', $exam->id)
                    ->where('student_id', $studentId)
                    ->delete();

                continue;
            }

            AcademicExamGrade::query()->updateOrCreate(
                ['academic_exam_id' => $exam->id, 'student_id' => $studentId],
                [
                    'score' => $absent ? null : $score,
                    'is_absent' => $absent,
                    'updated_by' => $request->user()?->id,
                ]
            );
        }

        return $this->show($request, $exam->fresh());
    }

    public function classSheet(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'exam.view');
        $data = $this->validatedScope($request);
        $this->assertTeacherCanAccess($request->user(), $data['subject_id'] ?? null, (int) $data['level_id']);

        $exams = $this->examsForScope($request->user(), $data)->get();
        $grades = $this->gradesForExams($exams);
        $students = $this->achievement->levelStudents((int) $data['level_id'], (int) $data['academic_year_id']);
        $level = Level::query()->findOrFail($data['level_id']);
        $year = AcademicYear::query()->find($data['academic_year_id']);

        $rows = $students->map(function (Student $student) use ($exams, $grades) {
            $cells = $exams->map(function (AcademicExam $exam) use ($student, $grades) {
                $grade = $grades->first(fn (AcademicExamGrade $row) => (int) $row->academic_exam_id === (int) $exam->id
                    && (int) $row->student_id === (int) $student->id);

                return [
                    'exam_id' => $exam->id,
                    'score' => $grade && ! $grade->is_absent && $grade->score !== null ? (float) $grade->score : null,
                    'is_absent' => (bool) ($grade?->is_absent ?? false),
                    'percent' => $grade?->percent((float) $exam->max_score),
                    'passed' => $grade?->passed((float) $exam->pass_score),
                ];
            })->values();

            return [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'cells' => $cells,
                'average' => $this->achievement->studentOverallAverage($student->id, $exams, $grades),
            ];
        })->values();

        return response()->json([
            'level' => $level->only(['id', 'code', 'name_ar', 'name_fr']),
            'academic_year' => $year?->only(['id', 'name']),
            'period' => $data['period'] ?? null,
            'exams' => AcademicExamResource::collection($exams->values())->resolve(),
            'students' => $rows,
            'class_average' => $this->achievement->classAverage($rows->pluck('average')),
        ]);
    }

    public function achievement(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'exam.view');
        $data = $this->validatedScope($request);
        $this->assertTeacherCanAccess($request->user(), null, (int) $data['level_id']);

        $year = AcademicYear::query()->findOrFail($data['academic_year_id']);
        $level = Level::query()->findOrFail($data['level_id']);
        $period = $data['period'] ?? 'term1';

        $subjects = $this->achievement->coreSubjects();
        $exams = $this->examsForAchievement($year->id, $level->id, $period, $subjects)
            ->map(fn (AcademicExam $exam) => $this->ensureTermExamScale($exam))
            ->values();
        $grades = $this->gradesForExams($exams);

        $previous = $this->achievement->previousPeriodKey($period, $year);
        $previousExams = ($previous['academic_year_id'] ?? null)
            ? $this->examsForAchievement((int) $previous['academic_year_id'], $level->id, (string) $previous['period'], $subjects)
                ->map(fn (AcademicExam $exam) => $this->ensureTermExamScale($exam))
                ->values()
            : collect();
        $previousGrades = $this->gradesForExams($previousExams);
        $manualPrevious = AcademicStudentPeriodStat::query()
            ->where('academic_year_id', $year->id)
            ->where('period', $period)
            ->whereIn('student_id', $this->achievement->levelStudents((int) $level->id, (int) $year->id)->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $yearExams = $this->examsForAchievement($year->id, $level->id, 'annual', $subjects)
            ->map(fn (AcademicExam $exam) => $this->ensureTermExamScale($exam))
            ->values();
        $yearGrades = $this->gradesForExams($yearExams);

        $user = $request->user();
        $students = $this->achievement->levelStudents((int) $level->id, (int) $year->id);
        $rows = $students->map(function (Student $student) use ($exams, $grades, $subjects, $previousExams, $previousGrades, $yearExams, $yearGrades, $manualPrevious) {
            $subjectMap = [];
            foreach ($subjects as $subject) {
                $subjectMap[(string) $subject->id] = $this->achievement->subjectAverage(
                    $student->id,
                    (int) $subject->id,
                    $exams,
                    $grades
                );
            }
            $current = $this->achievement->studentOverallAverage($student->id, $exams, $grades);
            $computedPrevious = $previousExams->isEmpty()
                ? null
                : $this->achievement->studentOverallAverage($student->id, $previousExams, $previousGrades);
            $manual = $manualPrevious->get($student->id);
            $previousAvg = $manual?->previous_average !== null
                ? (float) $manual->previous_average
                : $computedPrevious;
            $yearGradesRow = $this->achievement->studentYearGrades($student->id, $yearExams, $yearGrades);
            $termPass = $this->achievement->passThreshold($exams);
            $yearPass = $this->achievement->passThreshold($yearExams);

            return [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'subjects' => $subjectMap,
                'average' => $current,
                'passed' => $current !== null && $current >= $termPass,
                'terms' => [
                    'term1' => $yearGradesRow['term1'],
                    'term2' => $yearGradesRow['term2'],
                    'term3' => $yearGradesRow['term3'],
                ],
                'year_average' => $yearGradesRow['year'],
                'year_passed' => $yearGradesRow['year'] !== null && $yearGradesRow['year'] >= $yearPass,
                'previous_average' => $previousAvg,
                'previous_entered' => $manual?->previous_average !== null,
                'trend' => $this->achievement->trend($current, $previousAvg),
                'trend_delta' => $this->achievement->trendDelta($current, $previousAvg),
            ];
        })->values();

        return response()->json([
            'level' => $level->only(['id', 'code', 'name_ar', 'name_fr']),
            'academic_year' => $year->only(['id', 'name']),
            'period' => $period,
            'scale' => AcademicAchievementService::SCALE,
            'pass_score' => $this->achievement->passThreshold($exams),
            'editable' => $period !== 'annual' && $this->canEnterAchievementGrades($user),
            'previous_period' => $previous,
            'subjects' => $subjects->map(function (Subject $subject) use ($user, $level, $exams) {
                return [
                    ...$subject->only(['id', 'code', 'name_ar', 'name_fr']),
                    'can_grade' => $this->canGradeSubject($user, (int) $subject->id, (int) $level->id),
                    'pass_score' => $this->achievement->subjectPassScore($exams, (int) $subject->id),
                ];
            })->values(),
            'students' => $rows,
            'class_average' => $this->achievement->classAverage($rows->pluck('average')),
            'class_year_average' => $this->achievement->classAverage($rows->pluck('year_average')),
        ]);
    }

    public function saveAchievementGrades(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'exam.grade');
        $data = $this->validatedScope($request);
        abort_if(($data['period'] ?? 'term1') === 'annual', 422, 'Enter term grades, not the annual view.');
        $this->assertTeacherCanAccess($request->user(), null, (int) $data['level_id']);

        $payload = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'grades.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'grades.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pass_scores' => ['nullable', 'array'],
            'pass_scores.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'pass_scores.*.pass_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'previous_averages' => ['nullable', 'array'],
            'previous_averages.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'previous_averages.*.previous_average' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $coreIds = $this->achievement->coreSubjects()->pluck('id');
        $examsBySubject = [];

        foreach ($payload['pass_scores'] ?? [] as $row) {
            $subjectId = (int) $row['subject_id'];
            abort_unless($coreIds->contains($subjectId), 422, 'Only Quran, Arabic and Maths grades are stored here.');
            abort_unless($this->canGradeSubject($request->user(), $subjectId, (int) $data['level_id']), 403);
            $exam = $this->firstOrCreateTermExam(
                $request->user(),
                (int) $data['academic_year_id'],
                (int) $data['level_id'],
                (string) $data['period'],
                $subjectId
            );
            $exam->update(['pass_score' => (float) $row['pass_score']]);
            $examsBySubject[$subjectId] = $exam->fresh();
        }
        foreach ($payload['grades'] as $row) {
            $subjectId = (int) $row['subject_id'];
            abort_unless($coreIds->contains($subjectId), 422, 'Only Quran, Arabic and Maths grades are stored here.');
            $this->assertTeacherCanAccess($request->user(), $subjectId, (int) $data['level_id']);
            abort_unless($this->canGradeSubject($request->user(), $subjectId, (int) $data['level_id']), 403);

            if (! isset($examsBySubject[$subjectId])) {
                $examsBySubject[$subjectId] = $this->firstOrCreateTermExam(
                    $request->user(),
                    (int) $data['academic_year_id'],
                    (int) $data['level_id'],
                    (string) $data['period'],
                    $subjectId
                );
            }

            $exam = $examsBySubject[$subjectId];
            $score = array_key_exists('score', $row) && $row['score'] !== null && $row['score'] !== ''
                ? (float) $row['score']
                : null;
            abort_if($score !== null && $score > (float) $exam->max_score, 422, 'Score exceeds max.');

            if ($score === null) {
                AcademicExamGrade::query()
                    ->where('academic_exam_id', $exam->id)
                    ->where('student_id', (int) $row['student_id'])
                    ->delete();
                continue;
            }

            AcademicExamGrade::query()->updateOrCreate(
                ['academic_exam_id' => $exam->id, 'student_id' => (int) $row['student_id']],
                [
                    'score' => $score,
                    'is_absent' => false,
                    'updated_by' => $request->user()?->id,
                ]
            );
        }

        foreach ($payload['previous_averages'] ?? [] as $row) {
            $previous = array_key_exists('previous_average', $row) && $row['previous_average'] !== null && $row['previous_average'] !== ''
                ? (float) $row['previous_average']
                : null;
            AcademicStudentPeriodStat::query()->updateOrCreate(
                [
                    'academic_year_id' => (int) $data['academic_year_id'],
                    'student_id' => (int) $row['student_id'],
                    'period' => (string) $data['period'],
                ],
                [
                    'previous_average' => $previous,
                    'updated_by' => $request->user()?->id,
                ]
            );
        }

        return $this->achievement($request);
    }

    public function studentReport(Request $request, Student $student): JsonResponse
    {
        $this->authorizePermission($request, 'exam.view');
        $this->assertTeacherCanAccess($request->user(), null, $student->level_id ? (int) $student->level_id : null);

        return response()->json($this->buildStudentReport($request->user(), $student, $request->string('period')->toString() ?: null));
    }

    public function studentReportPdf(Request $request, Student $student): Response
    {
        $this->authorizePermission($request, 'exam.view');
        $this->assertTeacherCanAccess($request->user(), null, $student->level_id ? (int) $student->level_id : null);

        $report = $this->buildStudentReport($request->user(), $student, $request->string('period')->toString() ?: null);

        return $this->pdfDownload(
            view('reports.student-academic', [
                'locale' => $this->pdfLocale($request),
                'report' => $report,
            ])->render(),
            'academic-report-'.$student->id.'.pdf'
        );
    }

    public function indexPdf(Request $request): Response
    {
        $this->authorizePermission($request, 'exam.view');

        $filters = $request->only(['academic_year_id', 'level_id', 'subject_id', 'period', 'search']);
        $query = AcademicExam::query()
            ->with(['academicYear', 'level', 'subject'])
            ->withCount('grades')
            ->filtered($filters)
            ->latest('exam_date')
            ->latest('id');
        $this->restrictExamQuery($query, $request->user());

        return $this->pdfDownload(
            view('reports.academic-exams', [
                'locale' => $this->pdfLocale($request),
                'exams' => $query->get(),
            ])->render(),
            'academic-exams.pdf'
        );
    }

    public function showPdf(Request $request, AcademicExam $exam): Response
    {
        $payload = json_decode($this->show($request, $exam)->getContent(), true) ?: [];

        return $this->pdfDownload(
            view('reports.academic-exam-sheet', [
                'locale' => $this->pdfLocale($request),
                'payload' => $payload,
            ])->render(),
            'exam-'.$exam->id.'.pdf'
        );
    }

    public function achievementPdf(Request $request): Response
    {
        $payload = json_decode($this->achievement($request)->getContent(), true) ?: [];

        return $this->pdfDownload(
            view('reports.academic-achievement', [
                'locale' => $this->pdfLocale($request),
                'report' => $payload,
            ])->render(),
            'academic-achievement.pdf',
            'landscape'
        );
    }

    private function pdfLocale(Request $request): string
    {
        $locale = $request->string('locale')->toString();

        return in_array($locale, ['ar', 'fr', 'en'], true) ? $locale : 'ar';
    }

    private function pdfDownload(string $html, string $filename, string $orientation = 'portrait'): Response
    {
        $html = ArabicPdfGlyphs::shapeHtml($html);

        if (class_exists(\Dompdf\Dompdf::class)) {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isFontSubsettingEnabled', true);
            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', $orientation);
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

    private function buildStudentReport(?User $user, Student $student, ?string $period): array
    {
        $student->load(['level', 'academicYear']);
        abort_unless($student->level_id, 422, 'Student has no level.');
        $year = $student->academicYear
            ?? AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();
        $period = $period && in_array($period, AcademicExam::PERIODS, true) ? $period : 'term1';

        $subjects = $this->achievement->coreSubjects();
        $exams = $this->examsForAchievement((int) $year?->id, (int) $student->level_id, $period, $subjects);
        $grades = $this->gradesForExams($exams);

        $allExams = $this->examsForAchievement((int) $year?->id, (int) $student->level_id, 'annual', $subjects)
            ->concat($exams)
            ->unique('id')
            ->values();
        $allGrades = $this->gradesForExams($allExams);
        $subjectRows = $subjects->map(function (Subject $subject) use ($student, $exams, $grades) {
            $subjectExams = $exams->where('subject_id', $subject->id)->values();
            $examRows = $subjectExams->map(function (AcademicExam $exam) use ($student, $grades) {
                $grade = $grades->first(fn (AcademicExamGrade $row) => (int) $row->academic_exam_id === (int) $exam->id
                    && (int) $row->student_id === (int) $student->id);

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'exam_date' => $exam->exam_date?->toDateString(),
                    'max_score' => (float) $exam->max_score,
                    'pass_score' => (float) $exam->pass_score,
                    'score' => $grade && ! $grade->is_absent && $grade->score !== null ? (float) $grade->score : null,
                    'is_absent' => (bool) ($grade?->is_absent ?? false),
                    'percent' => $grade?->percent((float) $exam->max_score),
                    'passed' => $grade?->passed((float) $exam->pass_score),
                ];
            })->values();

            return [
                'subject' => $subject->only(['id', 'code', 'name_ar', 'name_fr']),
                'average' => $this->achievement->subjectAverage($student->id, (int) $subject->id, $exams, $grades),
                'pass_score' => $this->achievement->subjectPassScore($exams, (int) $subject->id),
                'exams' => $examRows,
            ];
        })->values();

        $periods = $allExams
            ->groupBy(fn (AcademicExam $exam) => $exam->academic_year_id.'-'.$exam->period)
            ->map(function (Collection $group) use ($student, $allGrades) {
                $first = $group->first();

                return [
                    'academic_year_id' => $first->academic_year_id,
                    'year_name' => $first->academicYear?->name,
                    'period' => $first->period,
                    'average' => $this->achievement->studentOverallAverage($student->id, $group, $allGrades),
                ];
            })
            ->sortBy(function (array $row) {
                $order = AcademicAchievementService::PERIOD_ORDER[$row['period']] ?? 9;

                return sprintf('%08d-%d', $row['academic_year_id'], $order);
            })
            ->values();

        $progress = $allExams->map(function (AcademicExam $exam) use ($student, $allGrades) {
            $grade = $allGrades->first(fn (AcademicExamGrade $row) => (int) $row->academic_exam_id === (int) $exam->id
                && (int) $row->student_id === (int) $student->id);
            if (! $grade) {
                return null;
            }

            return [
                'exam_id' => $exam->id,
                'title' => $exam->title,
                'exam_date' => $exam->exam_date?->toDateString(),
                'period' => $exam->period,
                'subject' => $exam->subject?->only(['id', 'name_ar', 'name_fr']),
                'score' => $grade->is_absent || $grade->score === null ? null : (float) $grade->score,
                'max_score' => (float) $exam->max_score,
                'percent' => $grade->percent((float) $exam->max_score),
                'is_absent' => $grade->is_absent,
            ];
        })->filter()->values();

        $classmates = $this->achievement->levelStudents((int) $student->level_id, $year?->id);
        $classAverages = $classmates->map(
            fn (Student $peer) => $this->achievement->studentOverallAverage($peer->id, $exams, $grades)
        );
        $current = $this->achievement->studentOverallAverage($student->id, $exams, $grades);
        $previous = $this->achievement->previousPeriodKey($period, $year);
        $previousExams = ($previous['academic_year_id'] ?? null)
            ? $this->examsForScope($user, [
                'academic_year_id' => $previous['academic_year_id'],
                'level_id' => $student->level_id,
                'period' => $previous['period'],
            ])->get()
            : collect();
        $previousAvg = $this->achievement->studentOverallAverage(
            $student->id,
            $previousExams,
            $this->gradesForExams($previousExams)
        );

        $yearExams = $this->examsForAchievement((int) $year?->id, (int) $student->level_id, 'annual', $subjects)
            ->map(fn (AcademicExam $exam) => $this->ensureTermExamScale($exam))
            ->values();
        $yearGrades = $this->gradesForExams($yearExams);
        $yearGradesRow = $this->achievement->studentYearGrades($student->id, $yearExams, $yearGrades);

        return [
            'student' => [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'level' => $student->level?->only(['id', 'code', 'name_ar', 'name_fr']),
                'academic_year' => $year?->only(['id', 'name']),
            ],
            'period' => $period,
            'scale' => AcademicAchievementService::SCALE,
            'pass_score' => $this->achievement->passThreshold($exams),
            'overall_average' => $current,
            'year_average' => $yearGradesRow['year'],
            'year_passed' => $yearGradesRow['year'] !== null && $yearGradesRow['year'] >= $this->achievement->passThreshold($yearExams),
            'terms' => [
                'term1' => $yearGradesRow['term1'],
                'term2' => $yearGradesRow['term2'],
                'term3' => $yearGradesRow['term3'],
            ],
            'class_average' => $this->achievement->classAverage($classAverages),
            'previous_average' => $previousAvg,
            'trend' => $this->achievement->trend($current, $previousAvg),
            'subjects' => $subjectRows,
            'periods' => $periods,
            'progress' => $progress,
        ];
    }

    private function examsForAchievement(int $yearId, int $levelId, string $period, Collection $subjects): Collection
    {
        $subjectIds = $subjects->pluck('id')->filter()->values();
        if ($subjectIds->isEmpty() || $yearId < 1) {
            return collect();
        }

        $periods = $period === 'annual' ? ['term1', 'term2', 'term3'] : [$period];

        return AcademicExam::query()
            ->with(['subject', 'level', 'academicYear'])
            ->where('academic_year_id', $yearId)
            ->where('level_id', $levelId)
            ->whereIn('period', $periods)
            ->whereIn('subject_id', $subjectIds)
            ->orderBy('exam_date')
            ->orderBy('id')
            ->get();
    }

    private function firstOrCreateTermExam(?User $user, int $yearId, int $levelId, string $period, int $subjectId): AcademicExam
    {
        $existing = AcademicExam::query()
            ->where('academic_year_id', $yearId)
            ->where('level_id', $levelId)
            ->where('period', $period)
            ->where('subject_id', $subjectId)
            ->orderByDesc('id')
            ->first();
        if ($existing) {
            return $this->ensureTermExamScale($existing);
        }

        $subject = Subject::query()->findOrFail($subjectId);

        return AcademicExam::query()->create([
            'academic_year_id' => $yearId,
            'level_id' => $levelId,
            'subject_id' => $subjectId,
            'period' => $period,
            'title' => $subject->name_ar,
            'exam_date' => now()->toDateString(),
            'max_score' => AcademicAchievementService::SCALE,
            'pass_score' => AcademicAchievementService::PASS_SCORE,
            'created_by' => $user?->id,
        ]);
    }

    private function ensureTermExamScale(AcademicExam $exam): AcademicExam
    {
        $target = AcademicAchievementService::SCALE;
        $max = (float) $exam->max_score;

        if ($max > 0 && abs($max - $target) >= 0.01) {
            $factor = $target / $max;
            AcademicExamGrade::query()
                ->where('academic_exam_id', $exam->id)
                ->whereNotNull('score')
                ->get()
                ->each(function (AcademicExamGrade $grade) use ($factor) {
                    $grade->update(['score' => round((float) $grade->score * $factor, 2)]);
                });
            $exam->max_score = $target;
            $exam->pass_score = round((float) $exam->pass_score * $factor, 2);
            $exam->save();
        }

        return $exam->refresh();
    }

    private function canEnterAchievementGrades(?User $user): bool
    {
        return (bool) ($user?->hasPermission('exam.grade') || $user?->hasPermission('exam.update'));
    }

    private function canGradeSubject(?User $user, int $subjectId, int $levelId): bool
    {
        if (! $this->canEnterAchievementGrades($user)) {
            return false;
        }
        if (! $this->isTeacherOnly($user)) {
            return true;
        }
        [$subjectIds, $levelIds] = $this->teacherScope($user);
        if ($subjectIds !== null && ! $subjectIds->contains($subjectId)) {
            return false;
        }
        if ($levelIds !== null && ! $levelIds->contains($levelId)) {
            return false;
        }

        return true;
    }

    private function examsForScope(?User $user, array $scope)
    {
        $query = AcademicExam::query()
            ->with(['subject', 'level', 'academicYear'])
            ->filtered($scope)
            ->orderBy('exam_date')
            ->orderBy('id');
        $this->restrictExamQuery($query, $user);

        return $query;
    }

    private function gradesForExams(Collection $exams): Collection
    {
        if ($exams->isEmpty()) {
            return collect();
        }

        return AcademicExamGrade::query()
            ->whereIn('academic_exam_id', $exams->pluck('id'))
            ->get();
    }

    private function validatedExam(Request $request, ?AcademicExam $exam = null): array
    {
        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        $data = $request->validate([
            'title' => [$exam ? 'sometimes' : 'required', 'string', 'max:190'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'level_id' => [$exam ? 'sometimes' : 'required', 'integer', 'exists:levels,id'],
            'subject_id' => [$exam ? 'sometimes' : 'required', 'integer', 'exists:subjects,id'],
            'period' => [$exam ? 'sometimes' : 'required', Rule::in(AcademicExam::PERIODS)],
            'exam_date' => [$exam ? 'sometimes' : 'required', 'date'],
            'max_score' => [$exam ? 'sometimes' : 'required', 'numeric', 'min:0.01', 'max:1000'],
            'pass_score' => [$exam ? 'sometimes' : 'required', 'numeric', 'min:0', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (! ($data['academic_year_id'] ?? null) && ! $exam) {
            abort_unless($year, 422, 'No academic year is configured.');
            $data['academic_year_id'] = $year->id;
        }

        $max = (float) ($data['max_score'] ?? $exam?->max_score);
        $pass = (float) ($data['pass_score'] ?? $exam?->pass_score);
        abort_if($pass > $max, 422, 'Pass score cannot exceed max score.');

        return $data;
    }

    private function validatedScope(Request $request): array
    {
        $year = AcademicYear::query()->current()->first()
            ?? AcademicYear::query()->latest('id')->first();

        $data = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'period' => ['nullable', Rule::in(AcademicExam::PERIODS)],
        ]);
        $data['academic_year_id'] = $data['academic_year_id'] ?? $year?->id;
        abort_unless($data['academic_year_id'], 422, 'No academic year is configured.');

        return $data;
    }

    private function restrictExamQuery($query, ?User $user): void
    {
        [$subjectIds, $levelIds] = $this->teacherScope($user);
        if ($subjectIds === null) {
            return;
        }
        $query->whereIn('subject_id', $subjectIds ?: [-1]);
        if ($levelIds !== null) {
            $query->whereIn('level_id', $levelIds ?: [-1]);
        }
    }

    /**
     * @return array{0: ?Collection, 1: ?Collection}
     */
    private function teacherScope(?User $user): array
    {
        if (! $this->isTeacherOnly($user)) {
            return [null, null];
        }

        $user->loadMissing(['teacher.subjects', 'teacher.levels']);
        $teacher = $user->teacher;
        $subjectIds = $teacher?->subjects?->pluck('id') ?? collect();
        $levelIds = $teacher?->levels?->pluck('id') ?? collect();

        return [
            $subjectIds,
            $levelIds->isEmpty() ? null : $levelIds,
        ];
    }

    private function assertCanViewExam(?User $user, AcademicExam $exam): void
    {
        $this->assertTeacherCanAccess($user, (int) $exam->subject_id, (int) $exam->level_id);
    }

    private function assertTeacherCanAccess(?User $user, ?int $subjectId, ?int $levelId): void
    {
        if (! $this->isTeacherOnly($user)) {
            return;
        }
        [$subjectIds, $levelIds] = $this->teacherScope($user);
        if ($subjectId !== null && $subjectIds !== null && ! $subjectIds->contains($subjectId)) {
            abort(403);
        }
        if ($levelId !== null && $levelIds !== null && ! $levelIds->contains($levelId)) {
            abort(403);
        }
    }

    private function canManageExams(?User $user): bool
    {
        return (bool) ($user?->hasPermission('exam.create') || $user?->hasPermission('exam.update'));
    }

    private function canManageExam(?User $user, AcademicExam $exam): bool
    {
        if (! $user?->hasPermission('exam.update') && ! $user?->hasPermission('exam.delete')) {
            return false;
        }
        if (! $this->isTeacherOnly($user)) {
            return true;
        }

        return $this->teacherOwns($user, $exam);
    }

    private function canGradeExam(?User $user, AcademicExam $exam): bool
    {
        if (! $user?->hasPermission('exam.grade') && ! $user?->hasPermission('exam.update')) {
            return false;
        }
        if (! $this->isTeacherOnly($user)) {
            return true;
        }

        return $this->teacherOwns($user, $exam);
    }

    private function teacherOwns(?User $user, AcademicExam $exam): bool
    {
        [$subjectIds, $levelIds] = $this->teacherScope($user);
        if ($subjectIds !== null && ! $subjectIds->contains((int) $exam->subject_id)) {
            return false;
        }
        if ($levelIds !== null && ! $levelIds->contains((int) $exam->level_id)) {
            return false;
        }

        return true;
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

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
