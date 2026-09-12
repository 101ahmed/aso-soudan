<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SocialVisit */
class SocialVisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'place' => $this->place,
            'visit_type' => $this->visit_type,
            'reason' => $this->reason,
            'visited_on' => $this->visited_on?->toDateString(),
            'visited_at' => $this->visited_at ? substr((string) $this->visited_at, 0, 5) : null,
            'visitors' => $this->visitors,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
