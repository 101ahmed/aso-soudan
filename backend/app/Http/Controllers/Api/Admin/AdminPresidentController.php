<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PresidentialArchiveItemResource;
use App\Http\Resources\PresidentialDirectiveResource;
use App\Http\Resources\PresidentialMeetingResource;
use App\Models\Department;
use App\Models\PresidentialArchiveItem;
use App\Models\PresidentialDirective;
use App\Models\PresidentialMeeting;
use App\Support\DepartmentRoleMap;
use App\Support\PresidentialWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminPresidentController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $this->authorizePresident($request);

        return response()->json([
            'upcoming_meetings' => PresidentialMeeting::query()->upcoming()->count(),
            'follow_up_meetings' => PresidentialMeeting::query()->where('classification', 'follow_up')->count(),
            'urgent_meetings' => PresidentialMeeting::query()->where('classification', 'urgent')->count(),
            'sent_directives' => PresidentialDirective::query()->count(),
            'unread_directives' => PresidentialDirective::query()->unread()->count(),
            'archive_count' => PresidentialArchiveItem::query()->count(),
        ]);
    }

    public function secretariats(Request $request): JsonResponse
    {
        $this->authorizePresident($request);

        $codes = DepartmentRoleMap::secretariatCodes();

        $departments = Department::query()
            ->active()
            ->whereIn('code', $codes)
            ->with(['users' => fn ($q) => $q->orderByPivot('is_primary', 'desc')])
            ->withCount(['presidentialDirectives as unread_directives_count' => fn ($q) => $q->unread()])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => $departments->map(function (Department $department) {
                $manager = PresidentialWorkspace::resolveManager($department);

                return [
                    'id' => $department->id,
                    'code' => $department->code,
                    'name_ar' => $department->name_ar,
                    'name_fr' => $department->name_fr,
                    'unread_directives_count' => (int) $department->unread_directives_count,
                    'officer' => [
                        'name_ar' => $department->officer_name_ar,
                        'name_fr' => $department->officer_name_fr,
                        'title_ar' => $department->officer_title_ar,
                        'title_fr' => $department->officer_title_fr,
                        'email' => $department->officer_email,
                    ],
                    'manager' => $manager ? [
                        'id' => $manager->id,
                        'name' => $manager->name,
                        'email' => $manager->email,
                    ] : null,
                    'managers' => $department->users->map(fn ($user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_primary' => (bool) ($user->pivot?->is_primary),
                    ])->values(),
                ];
            })->values(),
        ]);
    }

    public function meetingsIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePresident($request);

        return PresidentialMeetingResource::collection(
            PresidentialMeeting::query()
                ->filtered($request->only(['classification', 'status', 'scope', 'search']))
                ->orderByRaw("CASE WHEN status = 'upcoming' THEN 0 ELSE 1 END")
                ->orderBy('scheduled_at')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function meetingsStore(Request $request): JsonResponse
    {
        $this->authorizePresident($request);
        $data = $this->validatedMeeting($request);
        $data['created_by'] = $request->user()->id;
        $data['reference'] = $data['reference'] ?? PresidentialWorkspace::nextReference(PresidentialMeeting::class, 'اج');

        $meeting = PresidentialMeeting::query()->create($data);
        PresidentialWorkspace::syncMeetingArchive($meeting, $request->user()->id);

        return (new PresidentialMeetingResource($meeting->fresh()))->response()->setStatusCode(201);
    }

    public function meetingsUpdate(Request $request, PresidentialMeeting $meeting): PresidentialMeetingResource
    {
        $this->authorizePresident($request);
        $meeting->update($this->validatedMeeting($request, $meeting));
        PresidentialWorkspace::syncMeetingArchive($meeting->fresh(), $request->user()->id);

        return new PresidentialMeetingResource($meeting->fresh());
    }

    public function meetingsDestroy(Request $request, PresidentialMeeting $meeting): JsonResponse
    {
        $this->authorizePresident($request);
        $meeting->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function directivesIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePresident($request);

        return PresidentialDirectiveResource::collection(
            PresidentialDirective::query()
                ->with(['department', 'assignee', 'sender'])
                ->filtered($request->only(['classification', 'status', 'department_id', 'search']))
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function directivesStore(Request $request): JsonResponse
    {
        $this->authorizePresident($request);

        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['nullable', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:8000'],
            'classification' => ['nullable', Rule::in(PresidentialDirective::CLASSIFICATIONS)],
        ]);

        $department = Department::query()->findOrFail($data['department_id']);
        abort_unless(in_array($department->code, DepartmentRoleMap::secretariatCodes(), true), 422);

        $manager = PresidentialWorkspace::resolveManager($department, $data['assigned_to_user_id'] ?? null);

        $directive = PresidentialDirective::query()->create([
            'reference' => PresidentialWorkspace::nextReference(PresidentialDirective::class, 'ت'),
            'department_id' => $department->id,
            'assigned_to_user_id' => $manager?->id,
            'title' => $data['title'] ?? null,
            'body' => $data['body'],
            'classification' => $data['classification'] ?? 'info',
            'status' => 'sent',
            'sender_id' => $request->user()->id,
        ]);

        PresidentialWorkspace::syncDirectiveArchive($directive);

        return (new PresidentialDirectiveResource(
            $directive->fresh()->load(['department', 'assignee', 'sender'])
        ))->response()->setStatusCode(201);
    }

    public function archiveIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorizePresident($request);

        return PresidentialArchiveItemResource::collection(
            PresidentialArchiveItem::query()
                ->filtered($request->only(['category', 'decision_number', 'date_from', 'date_to', 'search']))
                ->latest('document_date')
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function archiveStore(Request $request): JsonResponse
    {
        $this->authorizePresident($request);
        $item = PresidentialArchiveItem::query()->create([
            ...$this->validatedArchive($request),
            'source_type' => 'manual',
            'created_by' => $request->user()->id,
        ]);

        return (new PresidentialArchiveItemResource($item))->response()->setStatusCode(201);
    }

    public function archiveUpdate(Request $request, PresidentialArchiveItem $archiveItem): PresidentialArchiveItemResource
    {
        $this->authorizePresident($request);
        $archiveItem->update($this->validatedArchive($request, $archiveItem));

        return new PresidentialArchiveItemResource($archiveItem->fresh());
    }

    public function archiveDestroy(Request $request, PresidentialArchiveItem $archiveItem): JsonResponse
    {
        $this->authorizePresident($request);
        $archiveItem->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function inboxIndex(Request $request, string $code): AnonymousResourceCollection
    {
        $this->authorizeInbox($request);
        $department = $this->department($request, $code);

        return PresidentialDirectiveResource::collection(
            PresidentialDirective::query()
                ->with(['department', 'assignee', 'sender'])
                ->forDepartment($department->id)
                ->filtered($request->only(['classification', 'status', 'search']))
                ->latest('id')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function inboxUpdate(Request $request, string $code, PresidentialDirective $directive): PresidentialDirectiveResource
    {
        $this->authorizeInbox($request, true);
        $department = $this->department($request, $code);
        abort_unless($directive->department_id === $department->id, 404);

        $data = $request->validate([
            'status' => ['nullable', Rule::in(PresidentialDirective::STATUSES)],
            'manager_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (($data['status'] ?? null) && $data['status'] !== 'sent' && blank($directive->read_at)) {
            $data['read_at'] = now();
        }

        $directive->update($data);

        return new PresidentialDirectiveResource($directive->fresh()->load(['department', 'assignee', 'sender']));
    }

    private function validatedMeeting(Request $request, ?PresidentialMeeting $meeting = null): array
    {
        $required = $meeting ? 'sometimes' : 'required';

        return $request->validate([
            'reference' => ['nullable', 'string', 'max:50'],
            'decision_number' => ['nullable', 'string', 'max:50'],
            'title_ar' => [$required, 'string', 'max:255'],
            'title_fr' => [$required, 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'classification' => ['nullable', Rule::in(PresidentialMeeting::CLASSIFICATIONS)],
            'status' => ['nullable', Rule::in(PresidentialMeeting::STATUSES)],
            'agenda_ar' => ['nullable', 'string'],
            'agenda_fr' => ['nullable', 'string'],
            'minutes_ar' => ['nullable', 'string'],
            'minutes_fr' => ['nullable', 'string'],
            'decisions_ar' => ['nullable', 'string'],
            'decisions_fr' => ['nullable', 'string'],
            'follow_up_ar' => ['nullable', 'string'],
            'follow_up_fr' => ['nullable', 'string'],
        ]);
    }

    private function validatedArchive(Request $request, ?PresidentialArchiveItem $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';

        $data = $request->validate([
            'category' => [$required, Rule::in(PresidentialArchiveItem::CATEGORIES)],
            'title_ar' => [$required, 'string', 'max:255'],
            'title_fr' => [$required, 'string', 'max:255'],
            'body_ar' => ['nullable', 'string'],
            'body_fr' => ['nullable', 'string'],
            'decision_number' => ['nullable', 'string', 'max:50'],
            'document_date' => ['nullable', 'date'],
        ]);

        $data['document_date'] = $data['document_date'] ?? now()->toDateString();

        return $data;
    }

    private function department(Request $request, string $code): Department
    {
        return $request->attributes->get('department')
            ?? Department::query()->where('code', $code)->firstOrFail();
    }

    private function authorizePresident(Request $request): void
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('PRESIDENT') || $user->hasRole('SUPER_ADMIN')), 403);
    }

    private function authorizeInbox(Request $request, bool $write = false): void
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->hasRole('SUPER_ADMIN') || $user->hasRole('PRESIDENT')) {
            return;
        }

        abort_unless($user->hasPermission('president.directive.inbox') || $user->hasPermission('inbox.view'), 403);

        if ($write) {
            abort_unless(
                $user->hasPermission('president.directive.inbox') || $user->hasPermission('inbox.update'),
                403
            );
        }
    }
}
