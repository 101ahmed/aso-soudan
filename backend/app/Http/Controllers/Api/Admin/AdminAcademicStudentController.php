<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\EducationStage;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
            ->paginate($request->integer('per_page', 20));

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
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'registered_at' => now(),
            ]);

            $this->syncSubjects($student, $data['subject_ids'] ?? []);
            $this->enrollInTeacherClasses($student, $request->user(), $data['subject_ids'] ?? []);

            return $student->load(['academicYear', 'educationStage', 'level', 'subjects']);
        });

        return (new StudentResource($student))->response()->setStatusCode(201);
    }

    public function show(Request $request, Student $student): StudentResource
    {
        $this->authorizePermission($request, 'student.view');

        return new StudentResource($student->load(['academicYear', 'educationStage', 'level', 'subjects']));
    }

    public function update(Request $request, Student $student): StudentResource
    {
        $this->authorizePermission($request, 'student.update');
        $data = $this->validatedStudent($request);

        $student = DB::transaction(function () use ($data, $student, $request) {
            $student->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'academic_year_id' => $data['academic_year_id'] ?? null,
                'education_stage_id' => $data['education_stage_id'] ?? null,
                'level_id' => $data['level_id'] ?? null,
                'status' => $data['status'] ?? $student->status,
                'notes' => $data['notes'] ?? null,
            ]);

            if (array_key_exists('subject_ids', $data)) {
                $this->syncSubjects($student, $data['subject_ids'] ?? []);
                $this->enrollInTeacherClasses($student, $request->user(), $data['subject_ids'] ?? []);
            }

            return $student->fresh()->load(['academicYear', 'educationStage', 'level', 'subjects']);
        });

        return new StudentResource($student);
    }

    public function destroy(Request $request, Student $student): JsonResponse
    {
        $this->authorizePermission($request, 'student.delete');
        $student->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validatedStudent(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(Student::GENDERS)],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'education_stage_id' => ['nullable', 'integer', 'exists:education_stages,id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'status' => ['nullable', Rule::in(Student::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);
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
        $user->loadMissing('teacher');
        $teacher = $user->teacher;
        if (! $teacher || ! $student->level_id || ! $student->academic_year_id) {
            return;
        }

        $classes = ClassGroup::query()
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $student->academic_year_id)
            ->where('level_id', $student->level_id)
            ->where('status', 'active')
            ->when($subjectIds !== [], fn ($q) => $q->whereIn('subject_id', $subjectIds))
            ->get();

        foreach ($classes as $class) {
            $student->classGroups()->syncWithoutDetaching([
                $class->id => [
                    'status' => 'active',
                    'enrolled_on' => now()->toDateString(),
                ],
            ]);
        }
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
