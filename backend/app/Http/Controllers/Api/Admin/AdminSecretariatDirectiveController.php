<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SecretariatDirectiveResource;
use App\Models\Department;
use App\Models\SecretariatDirective;
use App\Support\DepartmentRoleMap;
use App\Support\PresidentialWorkspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminSecretariatDirectiveController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->authorizeView($request);
        $department = $this->department($request);

        $direction = $request->string('direction')->toString() === 'sent' ? 'sent' : 'received';

        $query = SecretariatDirective::query()
            ->with(['senderDepartment', 'recipientDepartment', 'assignee', 'sender'])
            ->filtered($request->only(['classification', 'status', 'search']));

        if ($direction === 'sent') {
            $query->fromSender($department->id);
        } else {
            $query->forRecipient($department->id);
        }

        return SecretariatDirectiveResource::collection(
            $query->latest('id')->paginate($request->integer('per_page', 20))
        );
    }

    public function targets(Request $request, string $code): JsonResponse
    {
        $this->authorizeView($request);
        $department = $this->department($request);

        $items = Department::query()
            ->active()
            ->whereIn('code', DepartmentRoleMap::secretariatCodes())
            ->where('id', '!=', $department->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Department $item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name_ar' => $item->name_ar,
                'name_fr' => $item->name_fr,
                'officer' => [
                    'name_ar' => $item->officer_name_ar,
                    'name_fr' => $item->officer_name_fr,
                    'email' => $item->officer_email,
                ],
            ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->authorizeSend($request);
        $senderDepartment = $this->department($request);

        $data = $request->validate([
            'recipient' => ['required', Rule::in(['all', 'one'])],
            'recipient_department_id' => ['required_if:recipient,one', 'nullable', 'integer', 'exists:departments,id'],
            'title' => ['nullable', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:8000'],
            'classification' => ['nullable', Rule::in(SecretariatDirective::CLASSIFICATIONS)],
        ]);

        $recipients = $this->resolveRecipients($senderDepartment, $data);
        abort_if($recipients->isEmpty(), 422, 'No recipient secretariat.');

        $isBroadcast = $data['recipient'] === 'all';
        $broadcastId = $isBroadcast ? (string) Str::uuid() : null;
        $created = [];

        foreach ($recipients as $recipient) {
            $manager = PresidentialWorkspace::resolveManager($recipient);
            $created[] = SecretariatDirective::query()->create([
                'reference' => PresidentialWorkspace::nextReference(SecretariatDirective::class, 'تأ'),
                'broadcast_id' => $broadcastId,
                'is_broadcast' => $isBroadcast,
                'sender_department_id' => $senderDepartment->id,
                'recipient_department_id' => $recipient->id,
                'assigned_to_user_id' => $manager?->id,
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'classification' => $data['classification'] ?? 'info',
                'status' => 'sent',
                'sender_id' => $request->user()->id,
            ]);
        }

        $fresh = SecretariatDirective::query()
            ->with(['senderDepartment', 'recipientDepartment', 'assignee', 'sender'])
            ->whereIn('id', collect($created)->pluck('id'))
            ->orderBy('id')
            ->get();

        return SecretariatDirectiveResource::collection($fresh)
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $code, SecretariatDirective $secretariatDirective): SecretariatDirectiveResource
    {
        $this->authorizeUpdate($request);
        $department = $this->department($request);
        abort_unless($secretariatDirective->recipient_department_id === $department->id, 404);

        $data = $request->validate([
            'status' => ['nullable', Rule::in(SecretariatDirective::STATUSES)],
            'manager_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (($data['status'] ?? null) && $data['status'] !== 'sent' && blank($secretariatDirective->read_at)) {
            $data['read_at'] = now();
        }

        $secretariatDirective->update($data);

        return new SecretariatDirectiveResource(
            $secretariatDirective->fresh()->load(['senderDepartment', 'recipientDepartment', 'assignee', 'sender'])
        );
    }

    private function resolveRecipients(Department $sender, array $data)
    {
        $codes = DepartmentRoleMap::secretariatCodes();

        if (($data['recipient'] ?? '') === 'all') {
            return Department::query()
                ->active()
                ->whereIn('code', $codes)
                ->where('id', '!=', $sender->id)
                ->orderBy('sort_order')
                ->get();
        }

        $recipient = Department::query()->findOrFail($data['recipient_department_id']);
        abort_unless(in_array($recipient->code, $codes, true), 422);
        abort_if($recipient->id === $sender->id, 422, 'Cannot send a directive to the same secretariat.');

        return collect([$recipient]);
    }

    private function department(Request $request): Department
    {
        return $request->attributes->get('department');
    }

    private function authorizeView(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 403);
        abort_unless(
            $user->hasPermission('secretariat.directive.view')
            || $user->hasPermission('inbox.view')
            || $user->hasRole('PRESIDENT')
            || $user->hasRole('VICE_PRESIDENT'),
            403
        );
    }

    private function authorizeSend(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 403);
        abort_unless(
            $user->hasPermission('secretariat.directive.send')
            || $user->hasPermission('inbox.create')
            || $user->hasRole('SUPER_ADMIN'),
            403
        );
    }

    private function authorizeUpdate(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 403);
        abort_unless(
            $user->hasPermission('secretariat.directive.view')
            || $user->hasPermission('inbox.update')
            || $user->hasRole('PRESIDENT')
            || $user->hasRole('VICE_PRESIDENT')
            || $user->hasRole('SUPER_ADMIN'),
            403
        );
    }
}
