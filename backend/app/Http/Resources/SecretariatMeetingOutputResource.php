<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SecretariatMeetingOutput */
class SecretariatMeetingOutputResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'meeting_on' => $this->meeting_on?->toDateString(),
            'location' => $this->location,
            'attendees_ar' => $this->attendees_ar,
            'attendees_fr' => $this->attendees_fr,
            'agenda_ar' => $this->agenda_ar,
            'agenda_fr' => $this->agenda_fr,
            'outputs_ar' => $this->outputs_ar,
            'outputs_fr' => $this->outputs_fr,
            'follow_up_ar' => $this->follow_up_ar,
            'follow_up_fr' => $this->follow_up_fr,
            'notes' => $this->when($request->user(), $this->notes),
            'is_public' => (bool) $this->is_public,
            'recorded_by' => $this->when($request->user(), $this->recorded_by),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
