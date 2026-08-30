<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExternalPartnerResource;
use App\Models\ExternalPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminExternalPartnerController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'partner.view');

        return ExternalPartnerResource::collection(
            ExternalPartner::query()
                ->withCount('documents')
                ->filtered($request->only(['search', 'type', 'partnership_status']))
                ->latest('id')
                ->paginate($request->integer('per_page', 30))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'partner.create');

        $item = ExternalPartner::query()->create($this->validated($request));

        return (new ExternalPartnerResource($item))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $code, ExternalPartner $externalPartner): ExternalPartnerResource
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'partner.view');

        return new ExternalPartnerResource($externalPartner->loadCount('documents'));
    }

    public function update(Request $request, string $code, ExternalPartner $externalPartner): ExternalPartnerResource
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'partner.update');

        $externalPartner->update($this->validated($request, $externalPartner));

        return new ExternalPartnerResource($externalPartner->fresh()->loadCount('documents'));
    }

    public function destroy(Request $request, string $code, ExternalPartner $externalPartner): JsonResponse
    {
        $this->assertExternal($code);
        $this->authorizePermission($request, 'partner.delete');
        $externalPartner->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?ExternalPartner $item = null): array
    {
        $required = $item ? 'sometimes' : 'required';

        $data = $request->validate([
            'name_ar' => [$required, 'string', 'max:190'],
            'name_fr' => ['nullable', 'string', 'max:190'],
            'type' => ['nullable', Rule::in(ExternalPartner::TYPES)],
            'city' => ['nullable', 'string', 'max:120'],
            'website' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'description_ar' => ['nullable', 'string', 'max:4000'],
            'description_fr' => ['nullable', 'string', 'max:4000'],
            'partnership_status' => ['nullable', Rule::in(ExternalPartner::STATUSES)],
            'is_public' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        if (array_key_exists('is_public', $data)) {
            $data['is_public'] = filter_var($data['is_public'], FILTER_VALIDATE_BOOLEAN);
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
