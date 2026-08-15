<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminAnnouncementController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $department = $this->department($request);

        return AnnouncementResource::collection(
            Announcement::query()
                ->with('department')
                ->where('department_id', $department->id)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest('id')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->authorizePermission($request, 'announcement.create');
        $department = $this->department($request);
        $data = $this->validated($request);
        unset($data['image']);
        $data['department_id'] = $department->id;
        $data['author_id'] = $request->user()->id;
        $data['status'] = $this->resolveCreateStatus($request, $data['status'] ?? 'draft');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('announcements', 'public');
        }

        $item = Announcement::query()->create($data);

        return (new AnnouncementResource($item->load('department')))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $code, Announcement $announcement): AnnouncementResource
    {
        $this->assertSameDepartment($request, $announcement->department_id);

        return new AnnouncementResource($announcement->load('department'));
    }

    public function update(Request $request, string $code, Announcement $announcement): AnnouncementResource
    {
        $this->authorizePermission($request, 'announcement.update');
        $this->assertSameDepartment($request, $announcement->department_id);
        $data = $this->validated($request, $announcement);
        unset($data['image']);

        if (($data['status'] ?? null) === 'published') {
            $this->authorizePermission($request, 'announcement.publish');
        }

        if ($request->hasFile('image')) {
            if ($announcement->image_path) {
                Storage::disk('public')->delete($announcement->image_path);
            }
            $data['image_path'] = $request->file('image')->store('announcements', 'public');
        }

        $announcement->update($data);

        return new AnnouncementResource($announcement->fresh()->load('department'));
    }

    public function destroy(Request $request, string $code, Announcement $announcement): JsonResponse
    {
        $this->authorizePermission($request, 'announcement.delete');
        $this->assertSameDepartment($request, $announcement->department_id);
        if ($announcement->image_path) {
            Storage::disk('public')->delete($announcement->image_path);
        }
        $announcement->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function submit(Request $request, string $code, Announcement $announcement): AnnouncementResource
    {
        $this->authorizePermission($request, 'announcement.update');
        $this->assertSameDepartment($request, $announcement->department_id);
        $announcement->markPendingReview();

        return new AnnouncementResource($announcement->fresh()->load('department'));
    }

    public function publish(Request $request, string $code, Announcement $announcement): AnnouncementResource
    {
        $this->authorizePermission($request, 'announcement.publish');
        $this->assertSameDepartment($request, $announcement->department_id);
        $announcement->markPublished();

        return new AnnouncementResource($announcement->fresh()->load('department'));
    }

    public function archive(Request $request, string $code, Announcement $announcement): AnnouncementResource
    {
        $this->authorizePermission($request, 'announcement.update');
        $this->assertSameDepartment($request, $announcement->department_id);
        $announcement->markArchived();

        return new AnnouncementResource($announcement->fresh()->load('department'));
    }

    private function validated(Request $request, ?Announcement $item = null): array
    {
        return $request->validate([
            'title_ar' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'content_ar' => ['nullable', 'string'],
            'content_fr' => ['nullable', 'string'],
            'image' => [
                $item && filled($item->image_path) ? 'nullable' : 'required',
                'image',
                'max:5120',
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'show_on_secretariat' => ['sometimes', 'boolean'],
            'show_on_home' => ['sometimes', 'boolean'],
            'visibility' => ['nullable', Rule::in(['public', 'internal'])],
            'status' => ['nullable', Rule::in(['draft', 'pending_review', 'published', 'archived'])],
        ]);
    }

    private function department(Request $request): Department
    {
        return $request->attributes->get('department');
    }

    private function assertSameDepartment(Request $request, ?int $departmentId): void
    {
        abort_unless($departmentId && $departmentId === $this->department($request)->id, 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }

    private function resolveCreateStatus(Request $request, string $requested): string
    {
        if ($requested === 'published' && $request->user()->hasPermission('announcement.publish')) {
            return 'published';
        }
        if ($requested === 'pending_review') {
            return 'pending_review';
        }

        return 'draft';
    }
}
