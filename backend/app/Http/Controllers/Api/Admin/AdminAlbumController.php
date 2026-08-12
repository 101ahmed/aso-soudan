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

    public function storeMedia(Request $request, string $code, Album $album): JsonResponse
    {
        $this->authorizePermission($request, 'gallery.manage');
        $this->assertSameDepartment($request, $album->department_id);

        $request->validate([
            'image' => ['nullable', 'image', 'max:8192'],
            'images' => ['nullable', 'array'],
            'images.*' => ['nullable', 'image', 'max:8192'],
            'caption_ar' => ['nullable', 'string', 'max:255'],
            'caption_fr' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $files = $this->collectImageFiles($request);

        if ($files === []) {
            return response()->json([
                'message' => 'Image required.',
                'errors' => ['images' => ['Please select at least one image file.']],
            ], 422);
        }

        $created = [];
        $sort = (int) ($request->input('sort_order') ?? ($album->media()->max('sort_order') ?? 0));

        foreach ($files as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $sort++;
            $path = $file->store('albums/'.$album->id, 'public');

            $created[] = Media::query()->create([
                'album_id' => $album->id,
                'path' => $path,
                'disk' => 'public',
                'mime_type' => $file->getMimeType(),
                'type' => 'image',
                'caption_ar' => $request->input('caption_ar'),
                'caption_fr' => $request->input('caption_fr'),
                'sort_order' => $sort,
                'uploaded_by' => $request->user()->id,
            ]);

            if (! $album->cover_path) {
                $album->update(['cover_path' => $path]);
                $album->refresh();
            }
        }

        if ($created === []) {
            return response()->json([
                'message' => 'Upload failed.',
                'errors' => ['images' => ['Could not store files. Max size is 8 MB per image.']],
            ], 422);
        }

        return response()->json([
            'data' => MediaResource::collection(collect($created))->resolve(),
            'album' => (new AlbumResource($album->fresh()->load(['department', 'media'])))->resolve(),
        ], 201);
    }

    public function destroyMedia(Request $request, string $code, Album $album, Media $media): JsonResponse
    {
        $this->authorizePermission($request, 'gallery.manage');
        $this->assertSameDepartment($request, $album->department_id);
        abort_unless($media->album_id === $album->id, 404);

        $wasCover = $album->cover_path && $album->cover_path === $media->path;

        Storage::disk($media->disk ?: 'public')->delete($media->path);
        $media->delete();

        if ($wasCover) {
            $next = $album->media()->orderBy('sort_order')->orderBy('id')->first();
            $album->update(['cover_path' => $next?->path]);
        }

        return response()->json([
            'message' => 'Deleted.',
            'album' => (new AlbumResource($album->fresh()->load(['department', 'media'])))->resolve(),
        ]);
    }

    /**
     * @return list<\Illuminate\Http\UploadedFile>
     */
    private function collectImageFiles(Request $request): array
    {
        $files = [];

        if ($request->hasFile('images')) {
            $uploaded = $request->file('images');
            $files = is_array($uploaded) ? $uploaded : [$uploaded];
        } elseif ($request->hasFile('image')) {
            $files = [$request->file('image')];
        }

        return array_values(array_filter($files));
    }

    private function validated(Request $request, ?Album $album = null): array
    {
        return $request->validate([
            'title_ar' => [$album ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$album ? 'sometimes' : 'required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_fr' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'pending_review', 'published', 'archived'])],
            'show_on_home' => ['nullable', 'boolean'],
            'show_on_gallery' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'image', 'max:8192'],
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
