<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CouncilMeeting */
class CouncilMeetingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includeInternal = (bool) $request->user();

        return [
            'id' => $this->id,
            'council_code' => $this->council_code,
            'reference' => $this->reference,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'location' => $this->location,
            'status' => $this->status,
            'agenda_ar' => $this->agenda_ar,
            'agenda_fr' => $this->agenda_fr,
            'visibility' => $this->visibility,
            'minutes_ar' => $this->when($includeInternal, $this->minutes_ar),
            'minutes_fr' => $this->when($includeInternal, $this->minutes_fr),
            'attendances' => $this->whenLoaded('attendances', function () use ($includeInternal) {
                if (! $includeInternal) {
                    return [];
                }

                return $this->attendances->map(fn ($a) => [
                    'id' => $a->id,
                    'council_member_id' => $a->council_member_id,
                    'member_name' => $a->member?->full_name,
                    'status' => $a->status,
                    'note' => $a->note,
                ]);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
