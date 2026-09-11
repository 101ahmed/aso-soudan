<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SportsTeam */
class SportsTeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_fr' => $this->name_fr,
            'age_category' => $this->age_category,
            'sport' => $this->sport,
            'coach_ar' => $this->coach_ar,
            'coach_fr' => $this->coach_fr,
            'manager_ar' => $this->manager_ar,
            'manager_fr' => $this->manager_fr,
            'photo_url' => MediaUrl::absolute($this->photo_path),
            'players_count' => $this->whenCounted('players'),
            'ranking' => $this->ranking,
            'points' => $this->points,
            'played' => $this->played,
            'wins' => $this->wins,
            'draws' => $this->draws,
            'losses' => $this->losses,
            'goals_for' => $this->goals_for,
            'goals_against' => $this->goals_against,
            'notes_ar' => $this->notes_ar,
            'notes_fr' => $this->notes_fr,
            'is_public' => (bool) $this->is_public,
            'sort_order' => $this->sort_order,
            'players' => SportsPlayerResource::collection($this->whenLoaded('players')),
            'staff' => SportsStaffResource::collection($this->whenLoaded('staff')),
            'matches' => SportsMatchResource::collection($this->whenLoaded('matches')),
            'trainings' => SportsTrainingResource::collection($this->whenLoaded('trainings')),
            'tournaments' => SportsTournamentResource::collection($this->whenLoaded('tournaments')),
        ];
    }
}
