<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class AdminDepartmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('SUPER_ADMIN') || $user->hasRole('PRESIDENT')) {
            return DepartmentResource::collection(
                Department::query()->active()->orderBy('sort_order')->get()
            );
        }

        return DepartmentResource::collection(
            $user->departments()->where('is_active', true)->orderBy('sort_order')->get()
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
        abort_unless(
            $request->user()?->hasPermission('news.update')
            || $request->user()?->hasPermission('news.create')
            || $request->user()?->hasPermission('gallery.manage')
            || $request->user()?->hasRole('SUPER_ADMIN')
            || $request->user()?->hasRole('PRESIDENT'),
            403
        );

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
            'photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_photo') && $department->{$photoColumn}) {
            Storage::disk('public')->delete($department->{$photoColumn});
            $data[$photoColumn] = null;
        }

        if ($request->hasFile('photo')) {
            if ($department->{$photoColumn}) {
                Storage::disk('public')->delete($department->{$photoColumn});
            }
            $folder = $prefix === 'deputy' ? 'deputies' : 'officers';
            $data[$photoColumn] = $request->file('photo')->store($folder.'/'.$department->code, 'public');
        }

        unset($data['photo'], $data['remove_photo']);

        if (array_key_exists($publicColumn, $data)) {
            $data[$publicColumn] = filter_var($data[$publicColumn], FILTER_VALIDATE_BOOLEAN);
        }

        $department->update($data);

        return new DepartmentResource($department->fresh());
    }
}
