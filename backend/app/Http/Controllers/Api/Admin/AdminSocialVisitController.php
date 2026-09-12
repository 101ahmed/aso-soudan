<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SocialVisitResource;
use App\Models\SocialVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminSocialVisitController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.view');

        $filters = $request->only(['search', 'visit_type', 'status', 'from', 'to']);

        return SocialVisitResource::collection(
            SocialVisit::query()
                ->filtered($filters)
                ->latest('visited_on')
                ->latest('id')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.create');

        $data = $this->validated($request);
        $data['created_by'] = $request->user()?->id;
        $item = SocialVisit::query()->create($data);

        return (new SocialVisitResource($item))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $code, SocialVisit $socialVisit): SocialVisitResource
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.view');

        return new SocialVisitResource($socialVisit);
    }

    public function update(Request $request, string $code, SocialVisit $socialVisit): SocialVisitResource
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.update');

        $socialVisit->update($this->validated($request, $socialVisit));

        return new SocialVisitResource($socialVisit->fresh());
    }

    public function destroy(Request $request, string $code, SocialVisit $socialVisit): JsonResponse
    {
        $this->assertSocial($code);
        $this->authorizePermission($request, 'help.delete');
        $socialVisit->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?SocialVisit $item = null): array
    {
        return $request->validate([
            'full_name' => [$item ? 'sometimes' : 'required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'place' => [$item ? 'sometimes' : 'required', 'string', 'max:190'],
            'visit_type' => [$item ? 'sometimes' : 'required', Rule::in(SocialVisit::TYPES)],
            'reason' => [$item ? 'sometimes' : 'required', 'string', 'max:4000'],
            'visited_on' => [$item ? 'sometimes' : 'required', 'date'],
            'visited_at' => ['nullable', 'date_format:H:i'],
            'visitors' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(SocialVisit::STATUSES)],
            'notes' => ['nullable', 'string', 'max:4000'],
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
