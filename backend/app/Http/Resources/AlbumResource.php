<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Album */
class AlbumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'description_ar' => $this->description_ar,
            'description_fr' => $this->description_fr,
            'cover_path' => $this->cover_path,
            'cover_url' => $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null,
            'status' => $this->status,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name_ar' => $this->department->name_ar,
                'name_fr' => $this->department->name_fr,
            ]),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
