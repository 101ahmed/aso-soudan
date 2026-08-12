<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\NewsResource;
use App\Models\Album;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicContentController extends Controller
{
    public function departments(): AnonymousResourceCollection
    {
        return DepartmentResource::collection(
            Department::query()->active()->orderBy('sort_order')->get()
        );
    }

    public function secretariatFeed(string $code): JsonResponse
    {
        $department = Department::query()->where('code', $code)->active()->firstOrFail();

        $news = News::query()
            ->with('department')
            ->where('department_id', $department->id)
            ->published()
            ->latest('published_at')
            ->limit(8)
            ->get();

        $announcements = Announcement::query()
            ->with('department')
            ->where('department_id', $department->id)
            ->where('show_on_secretariat', true)
            ->where(function ($q) {
                $q->where('visibility', 'public')->orWhereNull('visibility');
            })
            ->currentlyActive()
            ->latest('starts_at')
            ->limit(8)
            ->get();

        $albums = Album::query()
            ->with(['department', 'media'])
            ->where('department_id', $department->id)
            ->published()
            ->latest('published_at')
            ->limit(6)
            ->get();

        return response()->json([
            'department' => (new DepartmentResource($department))->resolve(),
            'news' => NewsResource::collection($news)->resolve(),
            'announcements' => AnnouncementResource::collection($announcements)->resolve(),
            'albums' => AlbumResource::collection($albums)->resolve(),
        ]);
    }

    public function news(Request $request): AnonymousResourceCollection
    {
        $query = News::query()->with('department')->published();

        if ($request->filled('department')) {
            $query->whereHas('department', fn ($q) => $q->where('code', $request->string('department')));
        }
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }
        if ($request->boolean('home')) {
            $query->where('show_on_home', true);
        }

        return NewsResource::collection(
            $query->latest('published_at')->paginate($request->integer('per_page', 12))
        );
    }

    public function newsShow(string $slug): NewsResource
    {
        $news = News::query()->with('department')->published()->where('slug', $slug)->firstOrFail();

        return new NewsResource($news);
    }

    public function announcements(Request $request): AnonymousResourceCollection
    {
        $query = Announcement::query()
            ->with('department')
            ->currentlyActive()
            ->where(function ($q) {
                $q->where('visibility', 'public')->orWhereNull('visibility');
            });

        if ($request->filled('department')) {
            $query->whereHas('department', fn ($q) => $q->where('code', $request->string('department')));
        }
        if ($request->boolean('home')) {
            $query->where('show_on_home', true);
        }

        return AnnouncementResource::collection(
            $query->latest('starts_at')->paginate($request->integer('per_page', 12))
        );
    }

    public function albums(Request $request): AnonymousResourceCollection
    {
        $query = Album::query()
            ->with(['department', 'media'])
            ->published()
            ->where('show_on_gallery', true);

        if ($request->filled('department')) {
            $query->whereHas('department', fn ($q) => $q->where('code', $request->string('department')));
        }
        if ($request->boolean('home')) {
            $query->where('show_on_home', true);
        }

        return AlbumResource::collection(
            $query->latest('published_at')->paginate($request->integer('per_page', 12))
        );
    }

    public function albumShow(string $album): AlbumResource
    {
        $model = Album::query()
            ->with(['department', 'media'])
            ->where(function ($q) use ($album) {
                $q->where('slug', $album);
                if (is_numeric($album)) {
                    $q->orWhere('id', $album);
                }
            })
            ->firstOrFail();

        abort_unless($model->status === 'published', 404);

        return new AlbumResource($model);
    }
}
