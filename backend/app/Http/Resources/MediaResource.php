<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Media */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'url' => $this->path ? Storage::disk($this->disk ?: 'public')->url($this->path) : null,
            'mime_type' => $this->mime_type,
            'type' => $this->type,
            'caption_ar' => $this->caption_ar,
            'caption_fr' => $this->caption_fr,
            'sort_order' => $this->sort_order,
        ];
    }
}
