<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExternalContactRequestResource;
use App\Models\ExternalContactRequest;
use App\Models\ExternalPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminExternalContactRequestController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'extcontact.view');

        return ExternalContactRequestResource::collection(
            ExternalContactRequest::query()
                ->with('partner')
                ->filtered($request->only(['search', 'status']))
                ->latest('id')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'extcontact.create');

        $item = ExternalContactRequest::query()->create($this->validated($request));

        return (new ExternalContactRequestResource($item->load('partner')))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $code, ExternalContactRequest $externalContactRequest): ExternalContactRequestResource
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'extcontact.update');

        $externalContactRequest->update($this->validated($request, $externalContactRequest));

        return new ExternalContactRequestResource($externalContactRequest->fresh()->load('partner'));
    }

    public function destroy(Request $request, string $code, ExternalContactRequest $externalContactRequest): JsonResponse
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'extcontact.delete');
        $externalContactRequest->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?ExternalContactRequest $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';

        $data = $request->validate([
            'applicant_name' => [$required, 'string', 'max:190'],
            'applicant_phone' => ['nullable', 'string', 'max:50'],
            'applicant_email' => ['nullable', 'email', 'max:190'],
            'applicant_organization' => ['nullable', 'string', 'max:190'],
            'partner_id' => ['nullable', 'integer', Rule::exists('external_partners', 'id')],
            'partner_name' => ['nullable', 'string', 'max:190'],
            'reason' => [$required, 'string', 'max:4000'],
            'status' => ['nullable', Rule::in(ExternalContactRequest::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (! empty($data['partner_id']) && blank($data['partner_name'] ?? null)) {
            $partner = ExternalPartner::query()->find($data['partner_id']);
            if ($partner) {
                $data['partner_name'] = $partner->name_ar ?: $partner->name_fr;
            }
        }

        return $data;
    }

    private function assertExternal(string $code): void
    {
        abort_unless($code === 'external-relations', 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
