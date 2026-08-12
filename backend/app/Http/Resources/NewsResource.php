<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\News */
class NewsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'content_ar' => $this->content_ar,
            'content_fr' => $this->content_fr,
            'slug' => $this->slug,
            'image_path' => $this->image_path,
            'image_url' => MediaUrl::absolute($this->image_path),
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'show_on_home' => (bool) $this->show_on_home,
            'published_at' => $this->published_at?->toIso8601String(),
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name_ar' => $this->department->name_ar,
                'name_fr' => $this->department->name_fr,
            ]),
            'author_id' => $this->author_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
