<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CouncilMember */
class CouncilMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $public = ! $request->user();

        return [
            'id' => $this->id,
            'council_code' => $this->council_code,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'photo_path' => $this->photo_path,
            'photo_url' => MediaUrl::absolute($this->photo_path),
            'position_code' => $this->position_code,
            'position_ar' => $this->position_ar,
            'position_fr' => $this->position_fr,
            'bio_ar' => $this->bio_ar,
            'bio_fr' => $this->bio_fr,
            'started_at' => $this->started_at?->toDateString(),
            'ended_at' => $this->ended_at?->toDateString(),
            'status' => $this->status,
            'is_public' => (bool) $this->is_public,
            'sort_order' => $this->sort_order,
            'email' => $this->when(! $public, $this->email),
            'phone' => $this->when(! $public, $this->phone),
            'user_id' => $this->when(! $public, $this->user_id),
            'has_account' => $this->when(! $public, (bool) $this->user_id),
        ];
    }
}
