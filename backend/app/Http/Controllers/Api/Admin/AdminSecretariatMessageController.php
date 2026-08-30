<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SecretariatMessageResource;
use App\Models\Department;
use App\Models\SecretariatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminSecretariatMessageController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'inbox.view');
        $department = $this->department($request, $code);

        return SecretariatMessageResource::collection(
            SecretariatMessage::query()
                ->forDepartment($department->id)
                ->filtered($request->only(['search', 'status']))
                ->latest('id')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->authorizePermission($request, 'inbox.create');
        $department = $this->department($request, $code);

        $item = SecretariatMessage::query()->create([
            ...$this->validated($request),
            'department_id' => $department->id,
            'status' => $request->input('status', 'new'),
        ]);

        return (new SecretariatMessageResource($item))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $code, SecretariatMessage $secretariatMessage): SecretariatMessageResource
    {
        $this->authorizePermission($request, 'inbox.update');
        $department = $this->department($request, $code);
        abort_unless($secretariatMessage->department_id === $department->id, 404);

        $data = $this->validated($request, $secretariatMessage);
        if (($data['status'] ?? null) === 'read' && blank($secretariatMessage->read_at)) {
            $data['read_at'] = now();
        }
        $secretariatMessage->update($data);

        return new SecretariatMessageResource($secretariatMessage->fresh());
    }

    public function destroy(Request $request, string $code, SecretariatMessage $secretariatMessage): JsonResponse
    {
        $this->authorizePermission($request, 'inbox.delete');
        $department = $this->department($request, $code);
        abort_unless($secretariatMessage->department_id === $department->id, 404);
        $secretariatMessage->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?SecretariatMessage $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';

        return $request->validate([
            'sender_name' => [$required, 'string', 'max:190'],
            'sender_email' => [$required, 'email', 'max:190'],
            'sender_phone' => ['nullable', 'string', 'max:50'],
            'subject' => [$required, 'string', 'max:190'],
            'body' => [$required, 'string', 'max:5000'],
            'status' => ['nullable', Rule::in(SecretariatMessage::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function department(Request $request, string $code): Department
    {
        return $request->attributes->get('department')
            ?? Department::query()->where('code', $code)->firstOrFail();
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
