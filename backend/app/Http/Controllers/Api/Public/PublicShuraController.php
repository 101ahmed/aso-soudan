<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouncilMeetingResource;
use App\Http\Resources\CouncilMemberResource;
use App\Models\CouncilMeeting;
use App\Models\CouncilMember;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicShuraController extends Controller
{
    public function members(): AnonymousResourceCollection
    {
        return CouncilMemberResource::collection(
            CouncilMember::query()
                ->forCouncil('shura')
                ->publicVisible()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );
    }

    public function meetings(): AnonymousResourceCollection
    {
        return CouncilMeetingResource::collection(
            CouncilMeeting::query()
                ->forCouncil('shura')
                ->publicVisible()
                ->latest('scheduled_at')
                ->limit(12)
                ->get()
        );
    }
}
