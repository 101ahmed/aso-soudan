<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExternalContactRequest */
class ExternalContactRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'applicant_name' => $this->applicant_name,
            'applicant_phone' => $this->applicant_phone,
            'applicant_email' => $this->applicant_email,
            'applicant_organization' => $this->applicant_organization,
            'partner_id' => $this->partner_id,
            'partner_name' => $this->partner_name,
            'partner' => $this->whenLoaded('partner', fn () => $this->partner ? [
                'id' => $this->partner->id,
                'name_ar' => $this->partner->name_ar,
                'name_fr' => $this->partner->name_fr,
            ] : null),
            'reason' => $this->reason,
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
