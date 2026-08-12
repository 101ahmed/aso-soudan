<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Department */
class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $showOfficer = (bool) ($this->officer_is_public ?? true)
            || (bool) $request->user();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name_ar' => $this->name_ar,
            'name_fr' => $this->name_fr,
            'description_ar' => $this->description_ar,
            'description_fr' => $this->description_fr,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
            'officer' => $this->when($showOfficer, fn () => [
                'name_ar' => $this->officer_name_ar,
                'name_fr' => $this->officer_name_fr,
                'title_ar' => $this->officer_title_ar,
                'title_fr' => $this->officer_title_fr,
                'bio_ar' => $this->officer_bio_ar,
                'bio_fr' => $this->officer_bio_fr,
                'email' => $this->officer_email,
                'phone' => $this->when((bool) $request->user(), $this->officer_phone),
                'photo_path' => $this->officer_photo_path,
                'photo_url' => MediaUrl::absolute($this->officer_photo_path),
                'is_public' => (bool) ($this->officer_is_public ?? true),
            ]),
        ];
    }
}
