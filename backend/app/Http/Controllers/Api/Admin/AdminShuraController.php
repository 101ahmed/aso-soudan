<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouncilMeetingResource;
use App\Http\Resources\CouncilMemberResource;
use App\Models\CouncilMeeting;
use App\Models\CouncilMeetingAttendance;
use App\Models\CouncilMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminShuraController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $this->authorizeAny($request, ['shura.member.view', 'shura.meeting.view', 'news.view']);

        return response()->json([
            'members_count' => CouncilMember::query()->forCouncil('shura')->where('status', 'active')->count(),
            'meetings_count' => CouncilMeeting::query()->forCouncil('shura')->count(),
            'upcoming_meetings' => CouncilMeeting::query()
                ->forCouncil('shura')
                ->where('scheduled_at', '>=', now())
                ->count(),
            'public_members' => CouncilMember::query()->forCouncil('shura')->publicVisible()->count(),
        ]);
    }

    public function membersIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'shura.member.view');

        return CouncilMemberResource::collection(
            CouncilMember::query()
                ->forCouncil('shura')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderBy('sort_order')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function membersStore(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shura.member.manage');
        $data = $this->validatedMember($request);
        $data['council_code'] = 'shura';

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('council/shura', 'public');
        }

        $member = CouncilMember::query()->create($data);

        return (new CouncilMemberResource($member))->response()->setStatusCode(201);
    }

    public function membersUpdate(Request $request, CouncilMember $member): CouncilMemberResource
    {
        $this->authorizePermission($request, 'shura.member.manage');
        abort_unless($member->council_code === 'shura', 404);
        $data = $this->validatedMember($request, $member);

        if ($request->hasFile('photo')) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('council/shura', 'public');
        }

        $member->update($data);

        return new CouncilMemberResource($member->fresh());
    }

    public function membersDestroy(Request $request, CouncilMember $member): JsonResponse
    {
        $this->authorizePermission($request, 'shura.member.manage');
        abort_unless($member->council_code === 'shura', 404);
        $member->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function meetingsIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'shura.meeting.view');

        return CouncilMeetingResource::collection(
            CouncilMeeting::query()
                ->forCouncil('shura')
                ->with(['attendances.member'])
                ->latest('scheduled_at')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function meetingsStore(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'shura.meeting.manage');
        $data = $this->validatedMeeting($request);
        $data['council_code'] = 'shura';
        $data['created_by'] = $request->user()->id;
        $meeting = CouncilMeeting::query()->create($data);

        return (new CouncilMeetingResource($meeting))->response()->setStatusCode(201);
    }

    public function meetingsUpdate(Request $request, CouncilMeeting $meeting): CouncilMeetingResource
    {
        $this->authorizePermission($request, 'shura.meeting.manage');
        abort_unless($meeting->council_code === 'shura', 404);
        $meeting->update($this->validatedMeeting($request, $meeting));

        return new CouncilMeetingResource($meeting->fresh()->load(['attendances.member']));
    }

    public function meetingsDestroy(Request $request, CouncilMeeting $meeting): JsonResponse
    {
        $this->authorizePermission($request, 'shura.meeting.manage');
        abort_unless($meeting->council_code === 'shura', 404);
        $meeting->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function syncAttendance(Request $request, CouncilMeeting $meeting): CouncilMeetingResource
    {
        $this->authorizePermission($request, 'shura.meeting.manage');
        abort_unless($meeting->council_code === 'shura', 404);

        $data = $request->validate([
            'attendances' => ['required', 'array'],
            'attendances.*.council_member_id' => ['required', 'integer', 'exists:council_members,id'],
            'attendances.*.status' => ['required', Rule::in(['present', 'absent', 'excused'])],
            'attendances.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data['attendances'] as $row) {
            CouncilMeetingAttendance::query()->updateOrCreate(
                [
                    'council_meeting_id' => $meeting->id,
                    'council_member_id' => $row['council_member_id'],
                ],
                [
                    'status' => $row['status'],
                    'note' => $row['note'] ?? null,
                ]
            );
        }

        return new CouncilMeetingResource($meeting->fresh()->load(['attendances.member']));
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
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    private function validatedMeeting(Request $request, ?CouncilMeeting $meeting = null): array
    {
        return $request->validate([
            'reference' => ['nullable', 'string', 'max:50'],
            'title_ar' => [$meeting ? 'sometimes' : 'required', 'string', 'max:255'],
            'title_fr' => [$meeting ? 'sometimes' : 'required', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['planned', 'held', 'cancelled'])],
            'agenda_ar' => ['nullable', 'string'],
            'agenda_fr' => ['nullable', 'string'],
            'minutes_ar' => ['nullable', 'string'],
            'minutes_fr' => ['nullable', 'string'],
            'visibility' => ['nullable', Rule::in(['public', 'internal'])],
        ]);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }

    private function authorizeAny(Request $request, array $permissions): void
    {
        $user = $request->user();
        abort_unless($user && collect($permissions)->contains(fn ($p) => $user->hasPermission($p)), 403);
    }
}
