<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PresidentProfileResource;
use App\Models\PresidentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicPresidentController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'president' => (new PresidentProfileResource(
                    PresidentProfile::visiblePublic(PresidentProfile::OFFICE_PRESIDENT)
                ))->resolve($request),
                'vice_president' => (new PresidentProfileResource(
                    PresidentProfile::visiblePublic(PresidentProfile::OFFICE_VICE_PRESIDENT)
                ))->resolve($request),
            ],
        ]);
    }
}
