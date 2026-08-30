<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MediaDecisionResource;
use App\Models\MediaDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminMediaDecisionController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'decision.view');

        return MediaDecisionResource::collection(
            MediaDecision::query()
                ->with('department')
                ->filtered($request->only(['kind', 'status', 'search']))
                ->latest('decided_on')
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'decision.create');

        $item = MediaDecision::query()->create([
            ...$this->validated($request),
            'recorded_by' => $request->user()->id,
        ]);

        return (new MediaDecisionResource($item->load('department')))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $code, MediaDecision $mediaDecision): MediaDecisionResource
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'decision.update');
        $mediaDecision->update($this->validated($request, $mediaDecision));

        return new MediaDecisionResource($mediaDecision->fresh()->load('department'));
    }

    public function destroy(Request $request, string $code, MediaDecision $mediaDecision): JsonResponse
    {
        $this->assertMedia($code);
        $this->authorizePermission($request, 'decision.delete');
        $mediaDecision->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?MediaDecision $item = null): array
    {
        $data = $request->validate([
            'kind' => [$item ? 'sometimes' : 'required', Rule::in(MediaDecision::KINDS)],
            'title_ar' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'details_ar' => ['nullable', 'string', 'max:8000'],
            'details_fr' => ['nullable', 'string', 'max:8000'],
            'responsible_ar' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'responsible_fr' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'decided_on' => [$item ? 'sometimes' : 'required', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:decided_on'],
            'status' => ['nullable', Rule::in(MediaDecision::STATUSES)],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (array_key_exists('department_id', $data) && $data['department_id'] === '') {
            $data['department_id'] = null;
        }
        if (array_key_exists('due_on', $data) && $data['due_on'] === '') {
            $data['due_on'] = null;
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
