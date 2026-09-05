<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SecretariatMeetingOutputResource;
use App\Models\SecretariatMeetingOutput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminSecretariatMeetingOutputController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertGeneralSecretariat($code);
        $this->authorizePermission($request, 'decision.view');

        return SecretariatMeetingOutputResource::collection(
            SecretariatMeetingOutput::query()
                ->filtered($request->only(['search']))
                ->latest('meeting_on')
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function show(Request $request, string $code, SecretariatMeetingOutput $meetingOutput): SecretariatMeetingOutputResource
    {
        $this->assertGeneralSecretariat($code);
        $this->authorizePermission($request, 'decision.view');

        return new SecretariatMeetingOutputResource($meetingOutput);
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->assertGeneralSecretariat($code);
        $this->authorizePermission($request, 'decision.create');

        $item = SecretariatMeetingOutput::query()->create([
            ...$this->validated($request),
            'recorded_by' => $request->user()->id,
        ]);

        return (new SecretariatMeetingOutputResource($item))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $code, SecretariatMeetingOutput $meetingOutput): SecretariatMeetingOutputResource
    {
        $this->assertGeneralSecretariat($code);
        $this->authorizePermission($request, 'decision.update');
        $meetingOutput->update($this->validated($request, $meetingOutput));

        return new SecretariatMeetingOutputResource($meetingOutput->fresh());
    }

    public function destroy(Request $request, string $code, SecretariatMeetingOutput $meetingOutput): JsonResponse
    {
        $this->assertGeneralSecretariat($code);
        $this->authorizePermission($request, 'decision.delete');
        $meetingOutput->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?SecretariatMeetingOutput $item = null): array
    {
        $data = $request->validate([
            'title_ar' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$item ? 'sometimes' : 'required', 'string', 'max:255'],
            'meeting_on' => [$item ? 'sometimes' : 'required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'attendees_ar' => ['nullable', 'string', 'max:8000'],
            'attendees_fr' => ['nullable', 'string', 'max:8000'],
            'agenda_ar' => ['nullable', 'string', 'max:8000'],
            'agenda_fr' => ['nullable', 'string', 'max:8000'],
            'outputs_ar' => ['nullable', 'string', 'max:16000'],
            'outputs_fr' => ['nullable', 'string', 'max:16000'],
            'follow_up_ar' => ['nullable', 'string', 'max:8000'],
            'follow_up_fr' => ['nullable', 'string', 'max:8000'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'is_public' => ['sometimes', 'boolean'],
        ]);

        foreach (['location', 'attendees_ar', 'attendees_fr', 'agenda_ar', 'agenda_fr', 'outputs_ar', 'outputs_fr', 'follow_up_ar', 'follow_up_fr', 'notes'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function assertGeneralSecretariat(string $code): void
    {
        abort_unless($code === 'general', 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
