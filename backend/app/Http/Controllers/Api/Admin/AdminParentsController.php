<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouncilMeetingResource;
use App\Http\Resources\CouncilMemberResource;
use App\Http\Resources\ParentRegistrationResource;
use App\Http\Resources\ParentSurveyResource;
use App\Models\CouncilMeeting;
use App\Models\CouncilMember;
use App\Models\ParentRegistration;
use App\Models\ParentSurvey;
use App\Support\MapUrl;
use App\Support\StoredFileStore;
use App\Support\UploadRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminParentsController extends Controller
{
    public function membersIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'parents.member.view');

        return CouncilMemberResource::collection(
            CouncilMember::query()
                ->forCouncil('parents')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderBy('sort_order')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function membersStore(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'parents.member.manage');
        $data = CouncilMember::fillPositionLabels($this->validatedMember($request));
        unset($data['photo']);
        $data['council_code'] = 'parents';

        if ($request->hasFile('photo')) {
            $data['photo_path'] = StoredFileStore::store($request->file('photo'), 'parents')['path'];
        }

        $member = CouncilMember::query()->create($data);

        return (new CouncilMemberResource($member))->response()->setStatusCode(201);
    }

    public function membersUpdate(Request $request, CouncilMember $member): CouncilMemberResource
    {
        $this->authorizePermission($request, 'parents.member.manage');
        abort_unless($member->council_code === 'parents', 404);
        $data = CouncilMember::fillPositionLabels($this->validatedMember($request, $member));
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = StoredFileStore::replace($member->photo_path, $request->file('photo'), 'parents')['path'];
        }

        $member->update($data);

        return new CouncilMemberResource($member->fresh());
    }

    public function membersDestroy(Request $request, CouncilMember $member): JsonResponse
    {
        $this->authorizePermission($request, 'parents.member.manage');
        abort_unless($member->council_code === 'parents', 404);
        StoredFileStore::forget($member->photo_path);
        $member->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function registrationsIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'parents.registration.view');

        $query = ParentRegistration::query()->with('children')->withCount('children')->latest('id');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $term = '%'.$request->string('search')->toString().'%';
            $query->where(function ($inner) use ($term) {
                $inner->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term);
            });
        }

        return ParentRegistrationResource::collection(
            $query->paginate($request->integer('per_page', 30))
        );
    }

    public function registrationsUpdate(Request $request, ParentRegistration $registration): ParentRegistrationResource
    {
        $this->authorizePermission($request, 'parents.registration.manage');
        $data = $request->validate([
            'status' => ['required', Rule::in(ParentRegistration::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $registration->update($data);

        return new ParentRegistrationResource($registration->fresh()->load('children')->loadCount('children'));
    }

    public function registrationsDestroy(Request $request, ParentRegistration $registration): JsonResponse
    {
        $this->authorizePermission($request, 'parents.registration.manage');
        $registration->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function meetingsIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'parents.meeting.view');

        return CouncilMeetingResource::collection(
            CouncilMeeting::query()
                ->forCouncil('parents')
                ->latest('scheduled_at')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function meetingsStore(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'parents.meeting.manage');
        $data = $this->validatedMeeting($request);
        $data['council_code'] = 'parents';
        $data['created_by'] = $request->user()->id;
        $meeting = CouncilMeeting::query()->create($data);

        return (new CouncilMeetingResource($meeting))->response()->setStatusCode(201);
    }

    public function meetingsUpdate(Request $request, CouncilMeeting $meeting): CouncilMeetingResource
    {
        $this->authorizePermission($request, 'parents.meeting.manage');
        abort_unless($meeting->council_code === 'parents', 404);
        $meeting->update($this->validatedMeeting($request, $meeting));

        return new CouncilMeetingResource($meeting->fresh());
    }

    public function meetingsDestroy(Request $request, CouncilMeeting $meeting): JsonResponse
    {
        $this->authorizePermission($request, 'parents.meeting.manage');
        abort_unless($meeting->council_code === 'parents', 404);
        $meeting->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function surveysIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'parents.survey.view');

        return ParentSurveyResource::collection(
            ParentSurvey::query()
                ->withCount('responses')
                ->latest('id')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function surveysStore(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'parents.survey.manage');
        $survey = ParentSurvey::query()->create([
            ...$this->validatedSurvey($request),
            'created_by' => $request->user()->id,
        ]);

        return (new ParentSurveyResource($survey->loadCount('responses')))->response()->setStatusCode(201);
    }

    public function surveysUpdate(Request $request, ParentSurvey $survey): ParentSurveyResource
    {
        $this->authorizePermission($request, 'parents.survey.manage');
        $survey->update($this->validatedSurvey($request, $survey));

        return new ParentSurveyResource($survey->fresh()->loadCount('responses'));
    }

    public function surveysDestroy(Request $request, ParentSurvey $survey): JsonResponse
    {
        $this->authorizePermission($request, 'parents.survey.manage');
        $survey->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function surveyResponses(Request $request, ParentSurvey $survey): JsonResponse
    {
        $this->authorizePermission($request, 'parents.survey.view');

        return response()->json([
            'survey' => new ParentSurveyResource($survey->loadCount('responses')),
            'data' => $survey->responses()->latest('id')->get()->map(fn ($item) => [
                'id' => $item->id,
                'parent_name' => $item->parent_name,
                'email' => $item->email,
                'phone' => $item->phone,
                'answers' => $item->answers,
                'created_at' => $item->created_at?->toIso8601String(),
            ]),
        ]);
    }

    private function validatedMeeting(Request $request, ?CouncilMeeting $meeting = null): array
    {
        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:50'],
            'title_ar' => [$meeting ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$meeting ? 'sometimes' : 'required', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'map_url' => ['nullable', 'string', 'max:2048'],
            'status' => ['nullable', Rule::in(['planned', 'held', 'cancelled'])],
            'agenda_ar' => ['nullable', 'string'],
            'agenda_fr' => ['nullable', 'string'],
            'visibility' => ['nullable', Rule::in(['public', 'internal'])],
        ]);

        $data['map_url'] = MapUrl::normalize($data['map_url'] ?? null, $data['location'] ?? null);

        return $data;
    }

    private function validatedSurvey(Request $request, ?ParentSurvey $survey = null): array
    {
        $data = $request->validate([
            'title_ar' => [$survey ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$survey ? 'sometimes' : 'required', 'string', 'max:255'],
            'details_ar' => ['nullable', 'string', 'max:4000'],
            'details_fr' => ['nullable', 'string', 'max:4000'],
            'form_url' => ['nullable', 'string', 'max:500'],
            'questions' => ['nullable', 'array', 'max:30'],
            'questions.*' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(ParentSurvey::STATUSES)],
        ]);

        if (array_key_exists('questions', $data)) {
            $data['questions'] = array_values(array_filter(array_map(
                fn ($item) => trim((string) $item),
                $data['questions'] ?? []
            )));
        }

        if (($data['status'] ?? null) === 'published' && ! $survey?->published_at) {
            $data['published_at'] = now();
        }
        if (($data['status'] ?? null) === 'draft') {
            $data['published_at'] = null;
        }

        return $data;
    }

    private function validatedMember(Request $request, ?CouncilMember $member = null): array
    {
        return $request->validate([
            'first_name' => [$member ? 'sometimes' : 'required', 'string', 'max:100'],
            'last_name' => [$member ? 'sometimes' : 'required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:50'],
            'position_code' => ['nullable', Rule::in(CouncilMember::POSITIONS)],
            'position_ar' => ['nullable', 'string', 'max:255'],
            'position_fr' => ['nullable', 'string', 'max:255'],
            'bio_ar' => ['nullable', 'string'],
            'bio_fr' => ['nullable', 'string'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'former', 'suspended'])],
            'is_public' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'photo' => UploadRules::image(5120),
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
