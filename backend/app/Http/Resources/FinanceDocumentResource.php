<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\FinanceDocument */
class FinanceDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = (bool) $request->user();
        $showFile = $isAdmin || $this->is_published;

        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'body_ar' => $this->body_ar,
            'body_fr' => $this->body_fr,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'size' => $this->size,
            'is_published' => (bool) $this->is_published,
            'published_at' => $this->published_at?->toIso8601String(),
            'has_file' => filled($this->file_path),
            'file_path' => $this->when($isAdmin, $this->file_path),
            'file_url' => $this->when($showFile && filled($this->file_path), MediaUrl::absolute($this->file_path)),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
