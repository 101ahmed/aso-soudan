<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\HelpRequestResource;
use App\Models\HelpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminSocialHelpRequestController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.view');

        $filters = $request->only(['search', 'help_type', 'status']);

        return HelpRequestResource::collection(
            HelpRequest::query()
                ->filtered($filters)
                ->latest('id')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.create');

        $item = HelpRequest::query()->create($this->validated($request));

        return (new HelpRequestResource($item))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $code, HelpRequest $helpRequest): HelpRequestResource
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.view');

        return new HelpRequestResource($helpRequest);
    }

    public function update(Request $request, string $code, HelpRequest $helpRequest): HelpRequestResource
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.update');

        $helpRequest->update($this->validated($request, $helpRequest));

        return new HelpRequestResource($helpRequest->fresh());
    }

    public function destroy(Request $request, string $code, HelpRequest $helpRequest): JsonResponse
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.delete');
        $helpRequest->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?HelpRequest $item = null): array
    {
        return $request->validate([
            'full_name' => [$item ? 'sometimes' : 'required', 'string', 'max:190'],
            'phone' => [$item ? 'sometimes' : 'required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'city' => ['nullable', 'string', 'max:120'],
            'help_type' => [$item ? 'sometimes' : 'required', Rule::in(HelpRequest::TYPES)],
            'details' => [$item ? 'sometimes' : 'required', 'string', 'max:4000'],
            'family_size' => ['nullable', 'integer', 'min:1', 'max:30'],
            'status' => ['nullable', Rule::in(HelpRequest::STATUSES)],
            'admin_notes' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function assertSocial(string $code): void
    {
        abort_unless($code === 'social', 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
