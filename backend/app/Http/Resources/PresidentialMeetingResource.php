<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PresidentialMeeting */
class PresidentialMeetingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'decision_number' => $this->decision_number,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'location' => $this->location,
            'classification' => $this->classification,
            'status' => $this->status,
            'agenda_ar' => $this->agenda_ar,
            'agenda_fr' => $this->agenda_fr,
            'minutes_ar' => $this->minutes_ar,
            'minutes_fr' => $this->minutes_fr,
            'decisions_ar' => $this->decisions_ar,
            'decisions_fr' => $this->decisions_fr,
            'follow_up_ar' => $this->follow_up_ar,
            'follow_up_fr' => $this->follow_up_fr,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
