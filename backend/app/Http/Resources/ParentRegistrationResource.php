<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ParentRegistration */
class ParentRegistrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'address' => $this->address,
            'notes' => $this->notes,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'children' => $this->whenLoaded('children', fn () => $this->children->map(fn ($child) => [
                'id' => $child->id,
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
                'birth_date' => $child->birth_date?->toDateString(),
                'gender' => $child->gender,
                'level' => $child->level,
            ])->values()),
            'children_count' => $this->whenCounted('children', $this->children_count),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
