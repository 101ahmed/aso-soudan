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
        $authenticated = (bool) $request->user();
        $showOfficer = (bool) ($this->officer_is_public ?? true) || $authenticated;
        $showDeputy = (bool) ($this->deputy_is_public ?? true) || $authenticated;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name_ar' => $this->name_ar,
            'name_fr' => $this->name_fr,
            'description_ar' => $this->description_ar,
            'description_fr' => $this->description_fr,
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
            'unread_messages_count' => $this->when(
                isset($this->unread_messages_count),
                (int) $this->unread_messages_count
            ),
            'officer' => $this->when($showOfficer, fn () => $this->personCard('officer', $authenticated)),
            'deputy' => $this->when($showDeputy, fn () => $this->personCard('deputy', $authenticated)),
        ];
    }

    private function personCard(string $prefix, bool $authenticated): array
    {
        return [
            'name_ar' => $this->{"{$prefix}_name_ar"},
            'name_fr' => $this->{"{$prefix}_name_fr"},
            'title_ar' => $this->{"{$prefix}_title_ar"},
            'title_fr' => $this->{"{$prefix}_title_fr"},
            'bio_ar' => $this->{"{$prefix}_bio_ar"},
            'bio_fr' => $this->{"{$prefix}_bio_fr"},
            'email' => $this->{"{$prefix}_email"},
            'phone' => $this->when($authenticated, $this->{"{$prefix}_phone"}),
            'photo_path' => $this->{"{$prefix}_photo_path"},
            'photo_url' => MediaUrl::absolute($this->{"{$prefix}_photo_path"}),
            'is_public' => (bool) ($this->{"{$prefix}_is_public"} ?? true),
        ];
    }
}
