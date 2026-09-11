<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SportsJoinRequest */
class SportsJoinRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'full_name' => $this->full_name,
            'birth_date' => $this->birth_date?->toDateString(),
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'age_category' => $this->age_category,
            'position' => $this->position,
            'sports_team_id' => $this->sports_team_id,
            'for_national' => (bool) $this->for_national,
            'message' => $this->message,
            'status' => $this->status,
            'admin_notes' => $this->when($request->user(), $this->admin_notes),
            'created_at' => $this->created_at?->toIso8601String(),
            'team' => new SportsTeamResource($this->whenLoaded('team')),
        ];
    }
}
