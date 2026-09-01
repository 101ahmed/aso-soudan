<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\NewsResource;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\News;
use App\Support\StoredFileStore;
use App\Support\UploadRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminSiteContentController extends Controller
{
    public function newsIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'news.view');

        return NewsResource::collection(
            News::query()
                ->with('department')
                ->when($request->boolean('home'), fn ($q) => $q->where('show_on_home', true))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function newsStore(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'news.create');
        $data = $this->validatedNews($request);
        unset($data['image']);
        $data['department_id'] = $this->ownerDepartment()->id;
        $data['author_id'] = $request->user()->id;
        $data['show_on_home'] = $data['show_on_home'] ?? true;
        $data['status'] = $this->resolveCreateStatus($request, $data['status'] ?? 'draft', 'news.publish');

        if ($request->hasFile('image')) {
            $data['image_path'] = StoredFileStore::store($request->file('image'), 'news')['path'];
        }
        if (($data['status'] ?? '') === 'published') {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        $news = News::query()->create($data);

        return (new NewsResource($news->load('department')))->response()->setStatusCode(201);
    }

    public function newsUpdate(Request $request, News $news): NewsResource
    {
        $this->authorizePermission($request, 'news.update');
        $data = $this->validatedNews($request, $news);
        unset($data['image']);

        if (isset($data['status']) && $data['status'] === 'published') {
            $this->authorizePermission($request, 'news.publish');
            $data['published_at'] = $data['published_at'] ?? $news->published_at ?? now();
        }
        if ($request->hasFile('image')) {
            $data['image_path'] = StoredFileStore::replace($news->image_path, $request->file('image'), 'news')['path'];
        }

        $news->update($data);

        return new NewsResource($news->fresh()->load('department'));
    }

    public function newsDestroy(Request $request, News $news): JsonResponse
    {
        $this->authorizePermission($request, 'news.delete');
        StoredFileStore::forget($news->image_path);
        $news->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function newsPublish(Request $request, News $news): NewsResource
    {
        $this->authorizePermission($request, 'news.publish');
        $news->markPublished();

        return new NewsResource($news->fresh()->load('department'));
    }

    public function newsArchive(Request $request, News $news): NewsResource
    {
        $this->authorizePermission($request, 'news.update');
        $news->markArchived();

        return new NewsResource($news->fresh()->load('department'));
    }

    public function announcementsIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'announcement.view');

        return AnnouncementResource::collection(
            Announcement::query()
                ->with('department')
                ->when($request->boolean('home'), fn ($q) => $q->where('show_on_home', true))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function announcementsStore(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'announcement.create');
        $data = $this->validatedAnnouncement($request);
        unset($data['image']);
        $data['department_id'] = $this->ownerDepartment()->id;
        $data['author_id'] = $request->user()->id;
        $data['show_on_home'] = $data['show_on_home'] ?? true;
        $data['status'] = $this->resolveCreateStatus($request, $data['status'] ?? 'draft', 'announcement.publish');

        if ($request->hasFile('image')) {
            $data['image_path'] = StoredFileStore::store($request->file('image'), 'announcements')['path'];
        }

        $item = Announcement::query()->create($data);

        return (new AnnouncementResource($item->load('department')))->response()->setStatusCode(201);
    }

    public function announcementsUpdate(Request $request, Announcement $announcement): AnnouncementResource
    {
        $this->authorizePermission($request, 'announcement.update');
        $data = $this->validatedAnnouncement($request, $announcement);
        unset($data['image']);

        if (($data['status'] ?? null) === 'published') {
            $this->authorizePermission($request, 'announcement.publish');
        }
        if ($request->hasFile('image')) {
            $data['image_path'] = StoredFileStore::replace($announcement->image_path, $request->file('image'), 'announcements')['path'];
        }

        $announcement->update($data);

        return new AnnouncementResource($announcement->fresh()->load('department'));
    }

    public function announcementsDestroy(Request $request, Announcement $announcement): JsonResponse
    {
        $this->authorizePermission($request, 'announcement.delete');
        StoredFileStore::forget($announcement->image_path);
        $announcement->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function announcementsPublish(Request $request, Announcement $announcement): AnnouncementResource
    {
        $this->authorizePermission($request, 'announcement.publish');
        $announcement->markPublished();

        return new AnnouncementResource($announcement->fresh()->load('department'));
    }

    private function validatedNews(Request $request, ?News $news = null): array
    {
        $data = $request->validate([
            'title_ar' => [$news ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$news ? 'sometimes' : 'required', 'string', 'max:255'],
            'content_ar' => ['nullable', 'string'],
            'content_fr' => ['nullable', 'string'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('news', 'slug')->ignore($news?->id)],
            'status' => ['nullable', Rule::in(['draft', 'pending_review', 'published', 'archived'])],
            'is_featured' => ['sometimes', 'boolean'],
            'show_on_home' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'image' => UploadRules::image(5120),
        ]);

        if (isset($data['title_fr']) && blank($data['slug'] ?? null) && ! $news) {
            $data['slug'] = Str::slug($data['title_fr']).'-'.Str::lower(Str::random(4));
        }

        return $data;
    }

    private function validatedAnnouncement(Request $request, ?Announcement $item = null): array
    {
        return $request->validate([
            'title_ar' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'content_ar' => ['nullable', 'string'],
            'content_fr' => ['nullable', 'string'],
            'image' => UploadRules::image(5120, ! ($item && filled($item->image_path))),
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'show_on_secretariat' => ['sometimes', 'boolean'],
            'show_on_home' => ['sometimes', 'boolean'],
            'visibility' => ['nullable', Rule::in(['public', 'internal'])],
            'status' => ['nullable', Rule::in(['draft', 'pending_review', 'published', 'archived'])],
        ]);
    }

    private function ownerDepartment(): Department
    {
        return Department::query()->where('code', 'media')->first()
            ?? Department::query()->where('code', 'general')->firstOrFail();
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }

    private function resolveCreateStatus(Request $request, string $requested, string $publishPermission): string
    {
        if ($requested === 'published' && $request->user()->hasPermission($publishPermission)) {
            return 'published';
        }
        if ($requested === 'pending_review') {
            return 'pending_review';
        }

        return 'draft';
    }
}
