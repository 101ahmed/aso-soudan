<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PresidentialArchiveItem */
class PresidentialArchiveItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'body_ar' => $this->body_ar,
            'body_fr' => $this->body_fr,
            'decision_number' => $this->decision_number,
            'document_date' => $this->document_date?->toDateString(),
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
