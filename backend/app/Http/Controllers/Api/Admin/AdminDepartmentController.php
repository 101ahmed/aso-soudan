<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Support\DepartmentCardPhotoStore;
use App\Support\UploadRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
            'photo' => UploadRules::image(12288),
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
}
