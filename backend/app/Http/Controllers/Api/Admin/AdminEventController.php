<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Department;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminEventController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $department = $this->department($request);

        return EventResource::collection(
            Event::query()
                ->with('department')
                ->where('department_id', $department->id)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
                ->latest('starts_at')
                ->latest('id')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->authorizePermission($request, 'event.create');
        $department = $this->department($request);
        $data = $this->validated($request);
        $data['department_id'] = $department->id;
        $data['created_by'] = $request->user()->id;
        $data['status'] = $this->resolveCreateStatus($request, $data['status'] ?? 'draft');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('events', 'public');
        }

        if (($data['status'] ?? '') === 'published') {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        $event = Event::query()->create($data);

        return (new EventResource($event->load('department')))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $code, Event $event): EventResource
    {
        $this->assertSameDepartment($request, $event->department_id);

        return new EventResource($event->load('department'));
    }

    public function update(Request $request, string $code, Event $event): EventResource
    {
        $this->authorizePermission($request, 'event.update');
        $this->assertSameDepartment($request, $event->department_id);
        $data = $this->validated($request, $event);

        if (isset($data['status']) && $data['status'] === 'published') {
            $this->authorizePermission($request, 'event.publish');
            $data['published_at'] = $data['published_at'] ?? $event->published_at ?? now();
        }

        if ($request->hasFile('image')) {
            if ($event->image_path) {
                Storage::disk('public')->delete($event->image_path);
            }
            $data['image_path'] = $request->file('image')->store('events', 'public');
        }

        $event->update($data);

        return new EventResource($event->fresh()->load('department'));
    }

    public function destroy(Request $request, string $code, Event $event): JsonResponse
    {
        $this->authorizePermission($request, 'event.delete');
        $this->assertSameDepartment($request, $event->department_id);
        $event->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function publish(Request $request, string $code, Event $event): EventResource
    {
        $this->authorizePermission($request, 'event.publish');
        $this->assertSameDepartment($request, $event->department_id);
        $event->markPublished();

        return new EventResource($event->fresh()->load('department'));
    }

    public function archive(Request $request, string $code, Event $event): EventResource
    {
        $this->authorizePermission($request, 'event.update');
        $this->assertSameDepartment($request, $event->department_id);
        $event->markArchived();

        return new EventResource($event->fresh()->load('department'));
    }

    private function validated(Request $request, ?Event $event = null): array
    {
        $data = $request->validate([
            'type' => [$event ? 'sometimes' : 'required', Rule::in(Event::TYPES)],
            'title_ar' => [$event ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$event ? 'sometimes' : 'required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_fr' => ['nullable', 'string'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('events', 'slug')->ignore($event?->id)],
            'location_ar' => ['nullable', 'string', 'max:255'],
            'location_fr' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['nullable', Rule::in(['draft', 'pending_review', 'published', 'archived'])],
            'show_on_secretariat' => ['sometimes', 'boolean'],
            'show_on_home' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:12288'],
        ]);

        if (isset($data['title_fr']) && blank($data['slug'] ?? null) && ! $event) {
            $data['slug'] = Str::slug($data['title_fr']).'-'.Str::lower(Str::random(4));
        }

        return $data;
    }

    private function department(Request $request): Department
    {
        /** @var Department $department */
        $department = $request->attributes->get('department');

        return $department;
    }

    private function assertSameDepartment(Request $request, ?int $departmentId): void
    {
        abort_unless($departmentId && $departmentId === $this->department($request)->id, 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        if ($request->user()?->hasPermission($permission)) {
            return;
        }
        if ($permission === 'event.publish' && $request->user()?->hasPermission('event.create')) {
            return;
        }
        if ($permission === 'event.delete' && $request->user()?->hasPermission('event.update')) {
            return;
        }
        abort(403);
    }

    private function resolveCreateStatus(Request $request, string $requested): string
    {
        if ($requested === 'published' && (
            $request->user()->hasPermission('event.publish') || $request->user()->hasPermission('event.create')
        )) {
            return 'published';
        }
        if ($requested === 'pending_review') {
            return 'pending_review';
        }

        return 'draft';
    }
}
