<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherResource;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminAcademicTeacherController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'teacher.view');

        $teachers = Teacher::query()
            ->with(['user', 'subjects'])
            ->withCount('classGroups')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhereHas('user', fn ($q) => $q->where('email', 'like', $search));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 20));

        return TeacherResource::collection($teachers);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'teacher.create');
        $data = $this->validatedTeacher($request);

        $teacher = DB::transaction(function () use ($data) {
            $role = Role::query()->where('code', 'TEACHER')->firstOrFail();

            $user = User::query()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'locale' => $data['locale'] ?? 'ar',
                'status' => $data['status'] ?? 'active',
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);
            $user->roles()->sync([$role->id]);

            $teacher = Teacher::query()->create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? 'active',
                'hired_on' => $data['hired_on'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $teacher->subjects()->sync($data['subject_ids'] ?? []);

            return $teacher->load(['user', 'subjects'])->loadCount('classGroups');
        });

        return (new TeacherResource($teacher))->response()->setStatusCode(201);
    }

    public function show(Request $request, Teacher $teacher): TeacherResource
    {
        $this->authorizePermission($request, 'teacher.view');

        return new TeacherResource($teacher->load(['user', 'subjects'])->loadCount('classGroups'));
    }

    public function update(Request $request, Teacher $teacher): TeacherResource
    {
        $this->authorizeWrite($request);
        $data = $this->validatedTeacher($request, $teacher);

        $teacher = DB::transaction(function () use ($data, $teacher) {
            $userPayload = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'locale' => $data['locale'] ?? $teacher->user?->locale ?? 'ar',
                'status' => $data['status'] ?? $teacher->status,
            ];

            if (! empty($data['password'])) {
                $userPayload['password'] = $data['password'];
            }

            $teacher->user?->update($userPayload);

            $teacher->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? $teacher->status,
                'hired_on' => $data['hired_on'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (array_key_exists('subject_ids', $data)) {
                $teacher->subjects()->sync($data['subject_ids'] ?? []);
            }

            return $teacher->fresh()->load(['user', 'subjects'])->loadCount('classGroups');
        });

        return new TeacherResource($teacher);
    }

    public function destroy(Request $request, Teacher $teacher): JsonResponse
    {
        $this->authorizeWrite($request);

        DB::transaction(function () use ($teacher) {
            $teacher->user?->update(['status' => 'inactive']);
            $teacher->update(['status' => 'inactive']);
            $teacher->delete();
        });

        return response()->json(['message' => 'Deleted.']);
    }

    private function validatedTeacher(Request $request, ?Teacher $teacher = null): array
    {
        $userId = $teacher?->user_id;

        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', Rule::in(['fr', 'ar'])],
            'status' => ['nullable', Rule::in(Teacher::STATUSES)],
            'hired_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'password' => [$teacher ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);
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
