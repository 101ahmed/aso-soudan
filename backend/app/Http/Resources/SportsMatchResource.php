<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SportsMatch */
class SportsMatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sports_team_id' => $this->sports_team_id,
            'is_national' => (bool) $this->is_national,
            'competition_ar' => $this->competition_ar,
            'competition_fr' => $this->competition_fr,
            'opponent_ar' => $this->opponent_ar,
            'opponent_fr' => $this->opponent_fr,
            'played_on' => $this->played_on?->toDateString(),
            'venue' => $this->venue,
            'is_home' => (bool) $this->is_home,
            'goals_for' => $this->goals_for,
            'goals_against' => $this->goals_against,
            'status' => $this->status,
            'is_public' => (bool) $this->is_public,
        ];
    }
}
