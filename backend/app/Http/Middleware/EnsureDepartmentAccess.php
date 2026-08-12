<?php

namespace App\Http\Middleware;

use App\Models\Department;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDepartmentAccess
{
    public function handle(Request $request, Closure $next, string $mode = 'write'): Response
    {
        $user = $request->user();
        $code = $request->route('department')
            ?? $request->route('code')
            ?? $request->route('departmentCode');

        if (! $user || ! $user->isActive()) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        if (! is_string($code) || $code === '') {
            return response()->json(['message' => 'Department code required.'], 400);
        }

        $department = Department::query()->where('code', $code)->first();
        if (! $department || ! $department->is_active) {
            return response()->json(['message' => 'Department not found.'], 404);
        }

        $write = $mode !== 'read';
        if (! $user->canAccessDepartment($department, $write)) {
            return response()->json([
                'message' => 'غير مصرح لك بالدخول إلى هذا القسم / Accès non autorisé à ce secrétariat.',
            ], 403);
        }

        $request->attributes->set('department', $department);

        return $next($request);
    }
}
