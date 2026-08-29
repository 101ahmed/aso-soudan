<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberResource;
use App\Mail\MemberBroadcastMail;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class AdminStatisticsMemberController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizePermission($request, 'member.view');

        $filtered = Member::query()->filtered($request);

        $members = (clone $filtered)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($request->integer('per_page', 20));

        $cities = Member::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $emailCount = (clone $filtered)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->count();

        return MemberResource::collection($members)->additional([
            'cities' => $cities,
            'email_count' => $emailCount,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'member.create');
        $data = $this->validatedMember($request);

        $member = Member::query()->create([
            ...$this->memberPayload($data),
            'status' => $data['status'] ?? 'active',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'submitted_at' => now(),
        ]);

        return (new MemberResource($member))->response()->setStatusCode(201);
    }

    public function show(Request $request, Member $member): MemberResource
    {
        $this->authorizePermission($request, 'member.view');

        return new MemberResource($member);
    }

    public function update(Request $request, Member $member): MemberResource
    {
        $this->authorizePermission($request, 'member.update');
        $data = $this->validatedMember($request, $member);

        $payload = $this->memberPayload($data);
        if (($data['status'] ?? $member->status) !== $member->status) {
            $payload['reviewed_by'] = $request->user()->id;
            $payload['reviewed_at'] = now();
        }

        $member->update($payload);

        return new MemberResource($member->fresh());
    }

    public function destroy(Request $request, Member $member): JsonResponse
    {
        $this->authorizePermission($request, 'member.delete');
        $member->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function message(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'member.message');

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
            'member_ids' => ['nullable', 'array', 'max:200'],
            'member_ids.*' => ['integer', 'exists:members,id'],
            'apply_filters' => ['sometimes', 'boolean'],
        ]);

        $query = Member::query()->filtered($request);

        if (! empty($data['member_ids']) && ! $request->boolean('apply_filters')) {
            $query = Member::query()->whereIn('id', $data['member_ids']);
        }

        $members = $query->orderBy('id')->limit(200)->get();

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($members as $member) {
            $email = trim((string) $member->email);
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;

                continue;
            }

            try {
                Mail::to($email)->send(new MemberBroadcastMail(
                    $data['subject'],
                    $data['body'],
                    $member->full_name ?: $email,
                ));
                $sent++;
            } catch (Throwable $e) {
                report($e);
                $failed++;
            }
        }

        return response()->json([
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
            'total' => $members->count(),
        ]);
    }

    private function validatedMember(Request $request, ?Member $member = null): array
    {
        if ($request->input('email') === '') {
            $request->merge(['email' => null]);
        }

        $emailRule = ['nullable', 'email', 'max:190'];
        if ($request->filled('email')) {
            $unique = Rule::unique('members', 'email')->whereNull('deleted_at');
            if ($member) {
                $unique->ignore($member->id);
            }
            $emailRule[] = $unique;
        }

        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(Member::GENDERS)],
            'email' => $emailRule,
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'membership_type' => ['nullable', Rule::in(Member::MEMBERSHIP_TYPES)],
            'status' => ['nullable', Rule::in(Member::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function memberPayload(array $data): array
    {
        return [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'membership_type' => $data['membership_type'] ?? null,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        if ($request->user()?->hasPermission($permission)) {
            return;
        }

        abort(403);
    }
}
