<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SportsPlayer */
class SportsPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sports_team_id' => $this->sports_team_id,
            'is_national' => (bool) $this->is_national,
            'name_ar' => $this->name_ar,
            'name_fr' => $this->name_fr,
            'position' => $this->position,
            'number' => $this->number,
            'birth_date' => $this->birth_date?->toDateString(),
            'photo_url' => MediaUrl::absolute($this->photo_path),
            'appearances' => (int) $this->appearances,
            'goals' => (int) $this->goals,
            'assists' => (int) $this->assists,
            'yellow_cards' => (int) $this->yellow_cards,
            'red_cards' => (int) $this->red_cards,
            'is_public' => (bool) $this->is_public,
            'sort_order' => $this->sort_order,
            'team' => new SportsTeamResource($this->whenLoaded('team')),
        ];
    }
}
