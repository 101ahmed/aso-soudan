<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SportsTournament */
class SportsTournamentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sports_team_id' => $this->sports_team_id,
            'is_national' => (bool) $this->is_national,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'season' => $this->season,
            'location' => $this->location,
            'ranking' => $this->ranking,
            'notes_ar' => $this->notes_ar,
            'notes_fr' => $this->notes_fr,
            'is_public' => (bool) $this->is_public,
        ];
    }
}
