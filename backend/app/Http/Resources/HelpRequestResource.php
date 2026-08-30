<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\HelpRequest */
class HelpRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'city' => $this->city,
            'help_type' => $this->help_type,
            'details' => $this->details,
            'family_size' => $this->family_size,
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
