<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'description_ar' => $this->description_ar,
            'description_fr' => $this->description_fr,
            'slug' => $this->slug,
            'image_path' => $this->image_path,
            'image_url' => MediaUrl::absolute($this->image_path),
            'location' => $this->location,
            'location_ar' => $this->location_ar,
            'location_fr' => $this->location_fr,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $this->status,
            'show_on_secretariat' => (bool) $this->show_on_secretariat,
            'show_on_home' => (bool) $this->show_on_home,
            'published_at' => $this->published_at?->toIso8601String(),
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name_ar' => $this->department->name_ar,
                'name_fr' => $this->department->name_fr,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
