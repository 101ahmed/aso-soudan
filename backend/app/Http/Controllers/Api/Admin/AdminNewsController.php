<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\Department;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminNewsController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $department = $this->department($request);

        return NewsResource::collection(
            News::query()
                ->with('department')
                ->where('department_id', $department->id)
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->latest('id')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->authorizePermission($request, 'news.create');
        $department = $this->department($request);
        $data = $this->validated($request);
        $data['department_id'] = $department->id;
        $data['author_id'] = $request->user()->id;
        $data['status'] = $this->resolveCreateStatus($request, $data['status'] ?? 'draft', 'news.publish');

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        if (($data['status'] ?? '') === 'published') {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        $news = News::query()->create($data);

        return (new NewsResource($news->load('department')))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $code, News $news): NewsResource
    {
        $this->assertSameDepartment($request, $news->department_id);

        return new NewsResource($news->load('department'));
    }

    public function update(Request $request, string $code, News $news): NewsResource
    {
        $this->authorizePermission($request, 'news.update');
        $this->assertSameDepartment($request, $news->department_id);
        $data = $this->validated($request, $news);

        if (isset($data['status']) && $data['status'] === 'published') {
            $this->authorizePermission($request, 'news.publish');
            $data['published_at'] = $data['published_at'] ?? $news->published_at ?? now();
        }

        if ($request->hasFile('image')) {
            if ($news->image_path) {
                Storage::disk('public')->delete($news->image_path);
            }
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        $news->update($data);

        return new NewsResource($news->fresh()->load('department'));
    }

    public function destroy(Request $request, string $code, News $news): JsonResponse
    {
        $this->authorizePermission($request, 'news.delete');
        $this->assertSameDepartment($request, $news->department_id);
        $news->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function submit(Request $request, string $code, News $news): NewsResource
    {
        $this->authorizePermission($request, 'news.update');
        $this->assertSameDepartment($request, $news->department_id);
        $news->markPendingReview();

        return new NewsResource($news->fresh()->load('department'));
    }

    public function publish(Request $request, string $code, News $news): NewsResource
    {
        $this->authorizePermission($request, 'news.publish');
        $this->assertSameDepartment($request, $news->department_id);
        $news->markPublished();

        return new NewsResource($news->fresh()->load('department'));
    }

    public function archive(Request $request, string $code, News $news): NewsResource
    {
        $this->authorizePermission($request, 'news.update');
        $this->assertSameDepartment($request, $news->department_id);
        $news->markArchived();

        return new NewsResource($news->fresh()->load('department'));
    }

    private function validated(Request $request, ?News $news = null): array
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
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        if (isset($data['title_fr']) && blank($data['slug'] ?? null) && ! $news) {
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
