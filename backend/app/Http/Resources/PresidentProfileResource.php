<?php

namespace App\Http\Resources;

use App\Models\PresidentProfile;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PresidentProfile */
class PresidentProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $url = MediaUrl::absolute($this->photo_path);
        if ($url) {
            $url .= (str_contains($url, '?') ? '&' : '?').'v='.($this->updated_at?->getTimestamp() ?: time());
        }

        return [
            'office' => $this->office ?: PresidentProfile::OFFICE_PRESIDENT,
            'name_ar' => $this->name_ar,
            'name_fr' => $this->name_fr,
            'photo_path' => $this->when((bool) $request->user(), $this->photo_path),
            'photo_url' => $url,
            'is_public' => (bool) $this->is_public,
        ];
    }
}
