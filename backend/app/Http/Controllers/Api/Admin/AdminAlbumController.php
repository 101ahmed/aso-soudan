<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\MediaResource;
use App\Models\Album;
use App\Models\Department;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminAlbumController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $department = $this->department($request);

        return AlbumResource::collection(
            Album::query()
                ->with(['department', 'media'])
                ->where('department_id', $department->id)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest('id')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->authorizePermission($request, 'gallery.manage');
        $department = $this->department($request);
        $data = $this->validated($request);
        $data['department_id'] = $department->id;
        $data['created_by'] = $request->user()->id;
        $data['status'] = $this->resolveCreateStatus($request, $data['status'] ?? 'draft');

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $request->file('cover')->store('albums', 'public');
        }

        $album = Album::query()->create($data);

        return (new AlbumResource($album->load(['department', 'media'])))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $code, Album $album): AlbumResource
    {
        $this->assertSameDepartment($request, $album->department_id);

        return new AlbumResource($album->load(['department', 'media']));
    }

    public function update(Request $request, string $code, Album $album): AlbumResource
    {
        $this->authorizePermission($request, 'gallery.manage');
        $this->assertSameDepartment($request, $album->department_id);
        $data = $this->validated($request, $album);

        if (($data['status'] ?? null) === 'published') {
            $this->authorizePermission($request, 'gallery.publish');
        }

        if ($request->hasFile('cover')) {
            if ($album->cover_path) {
                Storage::disk('public')->delete($album->cover_path);
            }
            $data['cover_path'] = $request->file('cover')->store('albums', 'public');
        }

        $album->update($data);

        return new AlbumResource($album->fresh()->load(['department', 'media']));
    }

    public function destroy(Request $request, string $code, Album $album): JsonResponse
    {
        $this->authorizePermission($request, 'gallery.manage');
        $this->assertSameDepartment($request, $album->department_id);
        $album->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function publish(Request $request, string $code, Album $album): AlbumResource
    {
        $this->authorizePermission($request, 'gallery.publish');
        $this->assertSameDepartment($request, $album->department_id);
        $album->markPublished();

        return new AlbumResource($album->fresh()->load(['department', 'media']));
    }

    public function archive(Request $request, string $code, Album $album): AlbumResource
    {
        $this->authorizePermission($request, 'gallery.manage');
        $this->assertSameDepartment($request, $album->department_id);
        $album->markArchived();

        return new AlbumResource($album->fresh()->load(['department', 'media']));
    }

    public function storeMedia(Request $request, string $code, Album $album): MediaResource
    {
        $this->authorizePermission($request, 'gallery.manage');
        $this->assertSameDepartment($request, $album->department_id);

        $data = $request->validate([
            'image' => ['required', 'image', 'max:8192'],
            'caption_ar' => ['nullable', 'string', 'max:255'],
            'caption_fr' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $path = $request->file('image')->store('albums/'.$album->id, 'public');

        $media = Media::query()->create([
            'album_id' => $album->id,
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $request->file('image')->getMimeType(),
            'type' => 'image',
            'caption_ar' => $data['caption_ar'] ?? null,
            'caption_fr' => $data['caption_fr'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'uploaded_by' => $request->user()->id,
        ]);

        if (! $album->cover_path) {
            $album->update(['cover_path' => $path]);
        }

        return new MediaResource($media);
    }

    public function destroyMedia(Request $request, string $code, Album $album, Media $media): JsonResponse
    {
        $this->authorizePermission($request, 'gallery.manage');
        $this->assertSameDepartment($request, $album->department_id);
        abort_unless($media->album_id === $album->id, 404);

        Storage::disk($media->disk ?: 'public')->delete($media->path);
        $media->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?Album $album = null): array
    {
        return $request->validate([
            'title_ar' => [$album ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$album ? 'sometimes' : 'required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_fr' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'pending_review', 'published', 'archived'])],
            'cover' => ['nullable', 'image', 'max:5120'],
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
        if ($requested === 'published' && $request->user()->hasPermission('gallery.publish')) {
            return 'published';
        }
        if ($requested === 'pending_review') {
            return 'pending_review';
        }

        return 'draft';
    }
}
