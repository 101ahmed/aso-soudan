<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonPreparationResource;
use App\Models\LessonPreparation;
use App\Models\User;
use App\Support\ArabicPdfGlyphs;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AdminLessonPreparationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeView($request);

        $items = $this->scopedQuery($request)
            ->with(['subject', 'level', 'teacher'])
            ->orderByDesc('lesson_date')
            ->orderByDesc('id')
            ->get();

        return LessonPreparationResource::collection($items);
    }

    public function store(Request $request): LessonPreparationResource
    {
        $this->authorizeWrite($request, 'lesson_prep.create');

        $data = $this->validated($request);
        $user = $request->user();
        $data['created_by_user_id'] = $user->id;
        $data['teacher_id'] = $user->teacher?->id;

        $prep = LessonPreparation::query()->create($data);

        return new LessonPreparationResource($prep->load(['subject', 'level', 'teacher']));
    }

    public function show(Request $request, LessonPreparation $lessonPreparation): LessonPreparationResource
    {
        $this->authorizeView($request);
        $this->assertCanAccess($request, $lessonPreparation);

        return new LessonPreparationResource($lessonPreparation->load(['subject', 'level', 'teacher']));
    }

    public function pdf(Request $request, LessonPreparation $lessonPreparation): Response
    {
        $this->authorizeView($request);
        $this->assertCanAccess($request, $lessonPreparation);

        $lessonPreparation->load(['subject', 'level', 'teacher']);
        $locale = in_array($request->string('locale')->toString(), ['ar', 'fr', 'en'], true)
            ? $request->string('locale')->toString()
            : 'ar';

        $html = view('reports.lesson-preparation', [
            'locale' => $locale,
            'prep' => $lessonPreparation,
        ])->render();
        $html = ArabicPdfGlyphs::shapeHtml($html);
        $filename = 'lesson-prep-'.$lessonPreparation->id.'-'.($lessonPreparation->lesson_date?->toDateString() ?: 'fiche').'.pdf';

        if (class_exists(Dompdf::class)) {
            $options = new Options;
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isFontSubsettingEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
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

    public function update(Request $request, LessonPreparation $lessonPreparation): LessonPreparationResource
    {
        $this->authorizeWrite($request, 'lesson_prep.update');
        $this->assertCanAccess($request, $lessonPreparation);

        $lessonPreparation->update($this->validated($request));

        return new LessonPreparationResource($lessonPreparation->fresh()->load(['subject', 'level', 'teacher']));
    }

    public function destroy(Request $request, LessonPreparation $lessonPreparation): JsonResponse
    {
        $this->authorizeWrite($request, 'lesson_prep.delete');
        $this->assertCanAccess($request, $lessonPreparation);

        $lessonPreparation->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')->whereNull('deleted_at')],
            'level_id' => ['required', 'integer', Rule::exists('levels', 'id')->whereNull('deleted_at')],
            'lesson_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:190'],
            'unit' => ['nullable', 'string', 'max:190'],
            'objectives' => ['nullable', 'string', 'max:5000'],
            'skills' => ['nullable', 'string', 'max:5000'],
            'concepts' => ['nullable', 'string', 'max:5000'],
            'intro' => ['nullable', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:8000'],
            'activities' => ['nullable', 'string', 'max:8000'],
            'group_work' => ['nullable', 'string', 'max:5000'],
            'assessment' => ['nullable', 'string', 'max:5000'],
            'conclusion' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function scopedQuery(Request $request)
    {
        $query = LessonPreparation::query();
        $user = $request->user();

        if ($this->isTeacherOnly($user)) {
            $teacherId = $user->teacher?->id;
            $query->where(function ($inner) use ($user, $teacherId) {
                $inner->where('created_by_user_id', $user->id);
                if ($teacherId) {
                    $inner->orWhere('teacher_id', $teacherId);
                }
            });
        }

        return $query;
    }

    private function assertCanAccess(Request $request, LessonPreparation $prep): void
    {
        if (! $this->isTeacherOnly($request->user())) {
            return;
        }

        $user = $request->user();
        $owns = (int) $prep->created_by_user_id === (int) $user->id
            || ($user->teacher?->id && (int) $prep->teacher_id === (int) $user->teacher->id);

        abort_unless($owns, 403);
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

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            $user?->hasPermission('lesson_prep.view')
            || $user?->hasRole('TEACHER')
            || $user?->hasRole('ACADEMIC_SECRETARIAT'),
            403
        );
    }

    private function authorizeWrite(Request $request, string $permission): void
    {
        $user = $request->user();
        abort_unless(
            $user?->hasPermission($permission)
            || $user?->hasRole('TEACHER')
            || $user?->hasRole('ACADEMIC_SECRETARIAT'),
            403
        );
    }
}
