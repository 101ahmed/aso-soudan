<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
}
