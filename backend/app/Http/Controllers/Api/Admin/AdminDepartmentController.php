<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Support\DepartmentCardPhotoStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;

class AdminDepartmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $user = $request->user();

        $unreadCount = fn ($query) => $query->where('status', 'new');
        $unreadDirectives = fn ($query) => $query->where('status', 'sent');
        $withCounts = [
            'messages as unread_messages_count' => $unreadCount,
            'presidentialDirectives as unread_directives_count' => $unreadDirectives,
        ];

        if ($user->hasRole('SUPER_ADMIN') || $user->hasRole('PRESIDENT')) {
            return DepartmentResource::collection(
                Department::query()
                    ->active()
                    ->withCount($withCounts)
                    ->orderBy('sort_order')
                    ->get()
            );
        }

        return DepartmentResource::collection(
            $user->departments()
                ->where('is_active', true)
                ->withCount($withCounts)
                ->orderBy('sort_order')
                ->get()
        );
    }

    public function show(Request $request, string $code): DepartmentResource
    {
        $department = $request->attributes->get('department')
            ?? Department::query()->where('code', $code)->firstOrFail();

        return new DepartmentResource($department);
    }

    public function updateOfficer(Request $request, string $code): DepartmentResource
    {
        return $this->updatePersonCard($request, $code, 'officer');
    }

    public function updateDeputy(Request $request, string $code): DepartmentResource
    {
        return $this->updatePersonCard($request, $code, 'deputy');
    }

    private function updatePersonCard(Request $request, string $code, string $prefix): DepartmentResource
    {
        $department = $request->attributes->get('department')
            ?? Department::query()->where('code', $code)->firstOrFail();

        $photoColumn = "{$prefix}_photo_path";
        $publicColumn = "{$prefix}_is_public";

        $data = $request->validate([
            "{$prefix}_name_ar" => ['nullable', 'string', 'max:120'],
            "{$prefix}_name_fr" => ['nullable', 'string', 'max:120'],
            "{$prefix}_title_ar" => ['nullable', 'string', 'max:190'],
            "{$prefix}_title_fr" => ['nullable', 'string', 'max:190'],
            "{$prefix}_bio_ar" => ['nullable', 'string', 'max:2000'],
            "{$prefix}_bio_fr" => ['nullable', 'string', 'max:2000'],
            "{$prefix}_email" => ['nullable', 'email', 'max:190'],
            "{$prefix}_phone" => ['nullable', 'string', 'max:50'],
            $publicColumn => ['nullable', 'boolean'],
            'photo' => $this->photoRules(),
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_photo') && ($department->{$photoColumn} || DepartmentCardPhotoStore::has($department, $prefix))) {
            DepartmentCardPhotoStore::forget($department, $prefix);
            $department->refresh();
            $data[$photoColumn] = null;
        }

        unset($data['photo'], $data['remove_photo']);

        if (array_key_exists($publicColumn, $data)) {
            $data[$publicColumn] = filter_var($data[$publicColumn], FILTER_VALIDATE_BOOLEAN);
        }

        $department->update($data);

        if ($request->hasFile('photo')) {
            DepartmentCardPhotoStore::store($department, $prefix, $request->file('photo'));
        }

        return new DepartmentResource($department->fresh());
    }

    /**
     * Avoid Laravel image/mimes rules: they call guessExtension() via ext-fileinfo
     * on the temp upload path, which has no extension and crashes WAMP without fileinfo.
     *
     * @return list<mixed>
     */
    private function photoRules(): array
    {
        return [
            'nullable',
            'file',
            'max:12288',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if (! $value instanceof UploadedFile) {
                    return;
                }

                $ext = strtolower($value->getClientOriginalExtension()
                    ?: pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    return;
                }

                $head = (string) @file_get_contents($value->getRealPath() ?: $value->getPathname(), false, null, 0, 16);
                $looksLikeImage = str_starts_with($head, "\xFF\xD8\xFF")
                    || str_starts_with($head, "\x89PNG")
                    || str_starts_with($head, 'GIF8')
                    || str_starts_with($head, 'RIFF');

                if (! $looksLikeImage) {
                    $fail('The photo must be a jpg, jpeg, png, webp or gif image.');
                }
            },
        ];
    }
}
