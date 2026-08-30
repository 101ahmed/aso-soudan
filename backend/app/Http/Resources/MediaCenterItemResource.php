<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MediaCenterItem */
class MediaCenterItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'kind' => $this->kind,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'content_ar' => $this->content_ar,
            'content_fr' => $this->content_fr,
            'source_ar' => $this->source_ar,
            'source_fr' => $this->source_fr,
            'person_ar' => $this->person_ar,
            'person_fr' => $this->person_fr,
            'location_ar' => $this->location_ar,
            'location_fr' => $this->location_fr,
            'external_url' => $this->external_url,
            'image_path' => $this->image_path,
            'image_url' => MediaUrl::absolute($this->image_path),
            'occurred_on' => $this->occurred_on?->toDateString(),
            'status' => $this->status,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
