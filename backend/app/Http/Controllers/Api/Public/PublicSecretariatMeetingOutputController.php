<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\SecretariatMeetingOutputResource;
use App\Models\SecretariatMeetingOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicSecretariatMeetingOutputController extends Controller
{
    public function index(Request $request, string $code): AnonymousResourceCollection
    {
        abort_unless($code === 'general', 404);

        return SecretariatMeetingOutputResource::collection(
            SecretariatMeetingOutput::query()
                ->publicVisible()
                ->latest('meeting_on')
                ->latest('id')
                ->paginate($request->integer('per_page', 24))
        );
    }
}
