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

        $data = $request->validate([
            'officer_name_ar' => ['nullable', 'string', 'max:120'],
            'officer_name_fr' => ['nullable', 'string', 'max:120'],
            'officer_title_ar' => ['nullable', 'string', 'max:190'],
            'officer_title_fr' => ['nullable', 'string', 'max:190'],
            'officer_bio_ar' => ['nullable', 'string', 'max:2000'],
            'officer_bio_fr' => ['nullable', 'string', 'max:2000'],
            'officer_email' => ['nullable', 'email', 'max:190'],
            'officer_phone' => ['nullable', 'string', 'max:50'],
            'officer_is_public' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_photo') && $department->officer_photo_path) {
            Storage::disk('public')->delete($department->officer_photo_path);
            $data['officer_photo_path'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($department->officer_photo_path) {
                Storage::disk('public')->delete($department->officer_photo_path);
            }
            $data['officer_photo_path'] = $request->file('photo')->store('officers/'.$department->code, 'public');
        }

        unset($data['photo'], $data['remove_photo']);

        if (array_key_exists('officer_is_public', $data)) {
            $data['officer_is_public'] = filter_var($data['officer_is_public'], FILTER_VALIDATE_BOOLEAN);
        }

        $department->update($data);

        return new DepartmentResource($department->fresh());
    }
}
