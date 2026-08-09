<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PermissionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $permissions = Permission::query()
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->string('module')))
            ->orderBy('module')
            ->orderBy('code')
            ->get();

        return PermissionResource::collection($permissions);
    }
}
