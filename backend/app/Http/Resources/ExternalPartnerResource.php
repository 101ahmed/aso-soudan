<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExternalPartner */
class ExternalPartnerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = (bool) $request->user();

        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_fr' => $this->name_fr,
            'type' => $this->type,
            'city' => $this->city,
            'website' => $this->website,
            'email' => $this->when($isAdmin, $this->email),
            'phone' => $this->when($isAdmin, $this->phone),
            'description_ar' => $this->description_ar,
            'description_fr' => $this->description_fr,
            'partnership_status' => $this->when($isAdmin, $this->partnership_status),
            'is_public' => (bool) $this->is_public,
            'notes' => $this->when($isAdmin, $this->notes),
            'documents_count' => $this->whenCounted('documents'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
