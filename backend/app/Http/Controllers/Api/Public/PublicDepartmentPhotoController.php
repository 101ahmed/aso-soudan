<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Support\DepartmentCardPhotoStore;
use Illuminate\Http\Response;

class PublicDepartmentPhotoController extends Controller
{
    public function show(string $code, string $role): Response
    {
        abort_unless(in_array($role, DepartmentCardPhotoStore::ROLES, true), 404);

        $department = Department::query()->where('code', $code)->firstOrFail();
        $contents = DepartmentCardPhotoStore::contents($department, $role);
        abort_if($contents === null, 404);

        [$bytes, $mime] = $contents;
        $ext = match ($mime) {
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$role.'-'.$department->code.'.'.$ext.'"',
            'Cache-Control' => 'public, max-age=604800',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
