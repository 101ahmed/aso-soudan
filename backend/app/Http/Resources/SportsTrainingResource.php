<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SportsTraining */
class SportsTrainingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sports_team_id' => $this->sports_team_id,
            'weekday' => (int) $this->weekday,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'location' => $this->location,
            'notes_ar' => $this->notes_ar,
            'notes_fr' => $this->notes_fr,
            'is_public' => (bool) $this->is_public,
        ];
    }
}
