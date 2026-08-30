<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\MediaDecisionResource;
use App\Http\Resources\MediaCenterItemResource;
use App\Http\Resources\NewsResource;
use App\Models\Album;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Event;
use App\Models\EventRating;
use App\Models\MediaCenterItem;
use App\Models\MediaDecision;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\RateLimiter;

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

        $events = Event::query()
            ->with('department')
            ->withRatingsSummary()
            ->where('department_id', $department->id)
            ->published()
            ->where('show_on_secretariat', true)
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $mediaCenter = [];
        if ($code === 'media') {
            $mediaCenter = MediaCenterItem::query()
                ->published()
                ->latest('occurred_on')
                ->latest('id')
                ->limit(8)
                ->get();
        }

        return response()->json([
            'department' => (new DepartmentResource($department))->resolve(),
            'news' => NewsResource::collection($news)->resolve(),
            'announcements' => AnnouncementResource::collection($announcements)->resolve(),
            'albums' => AlbumResource::collection($albums)->resolve(),
            'events' => EventResource::collection($events)->resolve(),
            'media_center' => MediaCenterItemResource::collection($mediaCenter)->resolve(),
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

    public function decisions(Request $request): AnonymousResourceCollection
    {
        return MediaDecisionResource::collection(
            MediaDecision::query()
                ->where('show_on_home', true)
                ->whereNotIn('status', ['cancelled'])
                ->latest('decided_on')
                ->latest('id')
                ->paginate($request->integer('per_page', 8))
        );
    }

    public function mediaCenter(Request $request): AnonymousResourceCollection
    {
        $query = MediaCenterItem::query()->published();

        if ($request->filled('kind')) {
            $query->where('kind', $request->string('kind'));
        }

        return MediaCenterItemResource::collection(
            $query->latest('occurred_on')->latest('id')->paginate($request->integer('per_page', 24))
        );
    }

    public function mediaCenterShow(string $slug): MediaCenterItemResource
    {
        $item = MediaCenterItem::query()->published()->where('slug', $slug)->firstOrFail();

        return new MediaCenterItemResource($item);
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

    public function events(Request $request): AnonymousResourceCollection
    {
        $query = Event::query()->with('department')->withRatingsSummary()->published();

        if ($request->filled('department')) {
            $query->whereHas('department', fn ($q) => $q->where('code', $request->string('department')));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->boolean('home')) {
            $query->where('show_on_home', true);
        }
        if ($request->boolean('upcoming')) {
            $query->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '>=', now()->startOfDay());
            });
        }

        $ordered = $request->boolean('upcoming')
            ? $query->orderBy('starts_at')->orderByDesc('id')
            : $query->orderByDesc('starts_at')->orderByDesc('id');

        return EventResource::collection(
            $ordered->paginate($request->integer('per_page', 12))
        );
    }

    public function eventShow(string $slug): EventResource
    {
        $event = Event::query()->with('department')->withRatingsSummary()->published()->where('slug', $slug)->firstOrFail();

        return new EventResource($event);
    }

    public function rateEvent(Request $request, string $slug): JsonResponse
    {
        $key = 'event-rate:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return response()->json([
                'message' => 'Too many requests. Please try again later.',
            ], 429);
        }

        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'visitor_key' => ['required', 'uuid'],
        ]);

        $event = Event::query()->published()->where('slug', $slug)->firstOrFail();

        RateLimiter::hit($key, 3600);

        EventRating::query()->updateOrCreate(
            [
                'event_id' => $event->id,
                'visitor_hash' => hash('sha256', strtolower($data['visitor_key'])),
            ],
            ['stars' => $data['stars']],
        );

        $event = Event::query()->with('department')->withRatingsSummary()->findOrFail($event->id);

        return (new EventResource($event))->response();
    }
}
