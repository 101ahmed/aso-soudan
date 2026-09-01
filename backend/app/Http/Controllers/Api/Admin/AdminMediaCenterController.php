<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaCenterItemResource;
use App\Models\MediaCenterItem;
use App\Support\StoredFileStore;
use App\Support\UploadRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminMediaCenterController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'press.view');

        return MediaCenterItemResource::collection(
            MediaCenterItem::query()
                ->filtered($request->only(['kind', 'status', 'search']))
                ->latest('occurred_on')
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'press.create');

        $data = $this->validated($request);
        $data['recorded_by'] = $request->user()->id;
        if (($data['status'] ?? '') === MediaCenterItem::STATUS_PUBLISHED) {
            $data['published_at'] = $data['published_at'] ?? now();
        }
        if ($request->hasFile('image')) {
            $data['image_path'] = StoredFileStore::store($request->file('image'), 'media_center')['path'];
        }

        $item = MediaCenterItem::query()->create($data);

        return (new MediaCenterItemResource($item))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $code, MediaCenterItem $mediaCenterItem): MediaCenterItemResource
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'press.update');

        $data = $this->validated($request, $mediaCenterItem);
        if (($data['status'] ?? $mediaCenterItem->status) === MediaCenterItem::STATUS_PUBLISHED) {
            $data['published_at'] = $data['published_at'] ?? $mediaCenterItem->published_at ?? now();
        }
        if ($request->hasFile('image')) {
            $data['image_path'] = StoredFileStore::replace($mediaCenterItem->image_path, $request->file('image'), 'media_center')['path'];
        }

        $mediaCenterItem->update($data);

        return new MediaCenterItemResource($mediaCenterItem->fresh());
    }

    public function destroy(Request $request, string $code, MediaCenterItem $mediaCenterItem): JsonResponse
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'press.delete');
        StoredFileStore::forget($mediaCenterItem->image_path);
        $mediaCenterItem->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function publish(Request $request, string $code, MediaCenterItem $mediaCenterItem): MediaCenterItemResource
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'press.update');
        $mediaCenterItem->markPublished();

        return new MediaCenterItemResource($mediaCenterItem->fresh());
    }

    public function archive(Request $request, string $code, MediaCenterItem $mediaCenterItem): MediaCenterItemResource
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'press.update');
        $mediaCenterItem->markArchived();

        return new MediaCenterItemResource($mediaCenterItem->fresh());
    }

    private function validated(Request $request, ?MediaCenterItem $item = null): array
    {
        $data = $request->validate([
            'kind' => [$item ? 'sometimes' : 'required', Rule::in(MediaCenterItem::KINDS)],
            'title_ar' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'content_ar' => ['nullable', 'string', 'max:20000'],
            'content_fr' => ['nullable', 'string', 'max:20000'],
            'source_ar' => ['nullable', 'string', 'max:255'],
            'source_fr' => ['nullable', 'string', 'max:255'],
            'person_ar' => ['nullable', 'string', 'max:255'],
            'person_fr' => ['nullable', 'string', 'max:255'],
            'location_ar' => ['nullable', 'string', 'max:255'],
            'location_fr' => ['nullable', 'string', 'max:255'],
            'external_url' => ['nullable', 'string', 'max:2000'],
            'occurred_on' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in([
                MediaCenterItem::STATUS_DRAFT,
                MediaCenterItem::STATUS_PENDING_REVIEW,
                MediaCenterItem::STATUS_PUBLISHED,
                MediaCenterItem::STATUS_ARCHIVED,
            ])],
            'image' => UploadRules::image(8192),
        ]);

        unset($data['image']);

        foreach (['occurred_on', 'external_url', 'source_ar', 'source_fr', 'person_ar', 'person_fr', 'location_ar', 'location_fr'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function assertMedia(string $code): void
    {
        abort_unless($code === 'media', 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
