<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SportsStaff */
class SportsStaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sports_team_id' => $this->sports_team_id,
            'is_national' => (bool) $this->is_national,
            'kind' => $this->kind,
            'name_ar' => $this->name_ar,
            'name_fr' => $this->name_fr,
            'role_ar' => $this->role_ar,
            'role_fr' => $this->role_fr,
            'photo_url' => MediaUrl::absolute($this->photo_path),
            'is_public' => (bool) $this->is_public,
            'sort_order' => $this->sort_order,
        ];
    }
}
