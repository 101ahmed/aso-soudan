<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SportsCamp */
class SportsCampResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'location' => $this->location,
            'notes_ar' => $this->notes_ar,
            'notes_fr' => $this->notes_fr,
            'is_public' => (bool) $this->is_public,
        ];
    }
}
