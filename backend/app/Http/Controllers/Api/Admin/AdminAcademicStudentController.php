<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\AcademicYear;
use App\Models\ClassStaffAssignment;
use App\Models\EducationStage;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Support\ArabicPdfGlyphs;
use App\Support\StoredFileStore;
use App\Support\UploadRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminAcademicStudentController extends Controller
{
    public function catalog(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'student.view');

        return response()->json([
            'academic_years' => AcademicYear::query()->orderByDesc('id')->get(['id', 'name', 'is_current']),
            'stages' => EducationStage::query()
                ->where('is_active', true)
                ->with(['levels' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get(),
            'subjects' => Subject::query()->offered()->orderBy('name_ar')->get(['id', 'code', 'name_ar', 'name_fr']),
            'level_counts' => Student::query()
                ->selectRaw('level_id, COUNT(*) as total')
                ->where('status', 'active')
                ->whereNotNull('level_id')
                ->groupBy('level_id')
                ->pluck('total', 'level_id'),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'student.view');

        $students = Student::query()
            ->with(['academicYear', 'educationStage', 'level', 'subjects'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->integer('level_id')))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 100));

        ClassStaffAssignment::attachToStudents($students->getCollection());

        return StudentResource::collection($students);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'student.create');
        $data = $this->validatedStudent($request);

        $student = DB::transaction(function () use ($data, $request) {
            $student = Student::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'education_stage_id' => $data['education_stage_id'] ?? null,
                'level_id' => $data['level_id'] ?? null,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'photo_path' => $this->storePhoto($request, null),
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'registered_at' => now(),
            ]);

            $this->syncSubjects($student, $data['subject_ids'] ?? []);
            $student->enrollInActiveLevelClasses($data['subject_ids'] ?? []);

            $student = $student->load(['academicYear', 'educationStage', 'level', 'subjects']);
            ClassStaffAssignment::attachToStudents([$student]);

            return $student;
        });

        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    public function show(Request $request, Student $student): StudentResource
    {
        $this->authorizePermission($request, 'student.view');

        $student->load(['academicYear', 'educationStage', 'level', 'subjects']);
        ClassStaffAssignment::attachToStudents([$student]);

        return new StudentResource($student);
    }

    public function update(Request $request, Student $student): StudentResource
    {
        $this->authorizePermission($request, 'student.update');
        $data = $this->validatedStudent($request);

        $student = DB::transaction(function () use ($data, $student, $request) {
            $payload = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'education_stage_id' => $data['education_stage_id'] ?? null,
                'level_id' => $data['level_id'] ?? null,
                'status' => $data['status'] ?? $student->status,
                'notes' => $data['notes'] ?? null,
            ];

            $photoPath = $this->storePhoto($request, $student->photo_path, (bool) ($data['remove_photo'] ?? false));
            if ($photoPath !== $student->photo_path) {
                $payload['photo_path'] = $photoPath;
            }

            $student->update($payload);

            if (array_key_exists('subject_ids', $data)) {
                $this->syncSubjects($student, $data['subject_ids'] ?? []);
            }
            $student->enrollInActiveLevelClasses($data['subject_ids'] ?? $student->subjects()->pluck('subjects.id')->all());

            $student = $student->fresh()->load(['academicYear', 'educationStage', 'level', 'subjects']);
            ClassStaffAssignment::attachToStudents([$student]);

            return $student;
        });

        return new StudentResource($student);
    }

    public function destroy(Request $request, Student $student): JsonResponse
    {
        $this->authorizePermission($request, 'student.delete');
        StoredFileStore::forget($student->photo_path);
        $student->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function dossierPdf(Request $request, Student $student): Response
    {
        $this->authorizePermission($request, 'student.view');

        $student->load(['academicYear', 'educationStage', 'level', 'subjects']);
        ClassStaffAssignment::attachToStudents([$student]);
        $locale = in_array($request->string('locale')->toString(), ['ar', 'fr', 'en'], true)
            ? $request->string('locale')->toString()
            : 'ar';
        $photoSrc = $this->photoDataUri($student->photo_path);

        try {
            return $this->renderDossierPdf($student, $locale, $photoSrc);
        } catch (\Throwable $e) {
            if ($photoSrc) {
                report($e);

                return $this->renderDossierPdf($student, $locale, null);
            }

            throw $e;
        }
    }

    private function renderDossierPdf(Student $student, string $locale, ?string $photoSrc): Response
    {
        $html = view('reports.student-dossier', [
            'locale' => $locale,
            'student' => $student,
            'photoSrc' => $photoSrc,
        ])->render();
        $html = ArabicPdfGlyphs::shapeHtml($html);
        $filename = 'dossier-'.$student->id.'.pdf';

        if (class_exists(\Dompdf\Dompdf::class)) {
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isFontSubsettingEnabled', true);
            $dompdf = new \Dompdf\Dompdf($options);
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

    private function validatedStudent(Request $request): array
    {
        $this->normalizeStudentRequest($request);

        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(Student::GENDERS)],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'education_stage_id' => ['nullable', 'integer', 'exists:education_stages,id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'status' => ['nullable', Rule::in(Student::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'photo' => UploadRules::image(5120),
            'remove_photo' => ['sometimes', 'boolean'],
        ]);
    }

    private function normalizeStudentRequest(Request $request): void
    {
        if ($request->exists('remove_photo')) {
            $request->merge([
                'remove_photo' => filter_var($request->input('remove_photo'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($request->exists('subject_ids')) {
            $ids = $request->input('subject_ids');
            if (! is_array($ids)) {
                $ids = ($ids === '' || $ids === null) ? [] : [$ids];
            }
            $request->merge([
                'subject_ids' => array_values(array_filter(array_map('intval', $ids))),
            ]);
        }
    }

    private function storePhoto(Request $request, ?string $currentPath, bool $remove = false): ?string
    {
        if ($request->hasFile('photo')) {
            return StoredFileStore::replace($currentPath, $request->file('photo'), 'students')['path'];
        }

        if ($remove) {
            StoredFileStore::forget($currentPath);

            return null;
        }

        return $currentPath;
    }

    private function photoDataUri(?string $path): ?string
    {
        $uuid = StoredFileStore::uuidFromPath($path);
        if ($uuid === null) {
            return null;
        }

        $contents = StoredFileStore::contentsByUuid($uuid);
        if ($contents === null) {
            return null;
        }

        [$bytes, $mime] = $contents;
        if ($mime === 'image/jpeg' || str_starts_with($bytes, "\xFF\xD8\xFF")) {
            return 'data:image/jpeg;base64,'.base64_encode($bytes);
        }

        if (! in_array($mime, ['image/png', 'image/gif'], true)) {
            return null;
        }

        $jpeg = $this->rasterToJpeg($bytes);

        return $jpeg ? 'data:image/jpeg;base64,'.base64_encode($jpeg) : null;
    }

    private function rasterToJpeg(string $bytes): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return null;
        }

        $trueColor = imagecreatetruecolor(imagesx($image), imagesy($image));
        if ($trueColor === false) {
            imagedestroy($image);

            return null;
        }
        $white = imagecolorallocate($trueColor, 255, 255, 255);
        imagefilledrectangle($trueColor, 0, 0, imagesx($trueColor), imagesy($trueColor), $white);
        imagecopy($trueColor, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        ob_start();
        imagejpeg($trueColor, null, 85);
        imagedestroy($trueColor);
        $jpeg = ob_get_clean();

        return is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }

    private function syncSubjects(Student $student, array $subjectIds): void
    {
        $yearId = $student->academic_year_id;
        $sync = [];
        foreach ($subjectIds as $id) {
            $sync[$id] = ['academic_year_id' => $yearId];
        }
        $student->subjects()->sync($sync);
    }

    private function enrollInTeacherClasses(Student $student, User $user, array $subjectIds): void
    {
        $student->enrollInActiveLevelClasses($subjectIds);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        $user = $request->user();
        if ($user?->hasPermission($permission)) {
            return;
        }

        if ($user?->hasRole('TEACHER') && str_starts_with($permission, 'student.')) {
            return;
        }

        if ($permission === 'student.delete' && $user?->hasPermission('student.update')) {
            return;
        }

        abort(403);
    }
}
