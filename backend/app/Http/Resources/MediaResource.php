<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Media */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'url' => MediaUrl::absolute($this->path, $this->disk ?: 'public'),
            'mime_type' => $this->mime_type,
            'type' => $this->type,
            'caption_ar' => $this->caption_ar,
            'caption_fr' => $this->caption_fr,
            'sort_order' => $this->sort_order,
        ];
    }
}
