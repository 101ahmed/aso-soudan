<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExternalDocument */
class ExternalDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = (bool) $request->user();
        $showUrl = $isAdmin || $this->is_public;

        return [
            'id' => $this->id,
            'partner_id' => $this->partner_id,
            'partner' => $this->whenLoaded('partner', fn () => [
                'id' => $this->partner?->id,
                'name_ar' => $this->partner?->name_ar,
                'name_fr' => $this->partner?->name_fr,
            ]),
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'category' => $this->category,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'size' => $this->size,
            'is_public' => (bool) $this->is_public,
            'file_url' => $this->when($showUrl, MediaUrl::absolute($this->file_path)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
