<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\WomenMemberResource;
use App\Models\WomenMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AdminWomenMemberController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        $this->assertWomenChildren($code);
        $this->authorizePermission($request, 'women_member.view');

        $filters = $request->only(['search', 'gender', 'marital_status']);

        return WomenMemberResource::collection(
            WomenMember::query()
                ->filtered($filters)
                ->latest('id')
                ->paginate($request->integer('per_page', 20))
        );
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $this->assertWomenChildren($code);
        $this->authorizePermission($request, 'women_member.create');

        $data = $this->validated($request);
        $data['created_by'] = $request->user()?->id;
        $item = WomenMember::query()->create($data);

        return (new WomenMemberResource($item))->response()->setStatusCode(201);
    }

    public function show(Request $request, string $code, WomenMember $womenMember): WomenMemberResource
    {
        $this->assertWomenChildren($code);
        $this->authorizePermission($request, 'women_member.view');

        return new WomenMemberResource($womenMember);
    }

    public function update(Request $request, string $code, WomenMember $womenMember): WomenMemberResource
    {
        $this->assertWomenChildren($code);
        $this->authorizePermission($request, 'women_member.update');

        $womenMember->update($this->validated($request, $womenMember));

        return new WomenMemberResource($womenMember->fresh());
    }

    public function destroy(Request $request, string $code, WomenMember $womenMember): JsonResponse
    {
        $this->assertWomenChildren($code);
        $this->authorizePermission($request, 'women_member.delete');
        $womenMember->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function validated(Request $request, ?WomenMember $item = null): array
    {
        return $request->validate([
            'full_name' => [$item ? 'sometimes' : 'required', 'string', 'max:190'],
            'gender' => [$item ? 'sometimes' : 'required', Rule::in(WomenMember::GENDERS)],
            'residence' => ['nullable', 'string', 'max:190'],
            'marital_status' => ['nullable', Rule::in(WomenMember::MARITAL_STATUSES)],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:30'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function assertWomenChildren(string $code): void
    {
        abort_unless($code === 'women-children', 404);
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless($request->user()?->hasPermission($permission), 403);
    }
}
