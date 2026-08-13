<?php

namespace App\Http\Resources;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Teacher */
class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'status' => $this->status,
            'hired_on' => $this->hired_on?->toDateString(),
            'notes' => $this->notes,
            'email' => $this->whenLoaded('user', fn () => $this->user?->email),
            'locale' => $this->whenLoaded('user', fn () => $this->user?->locale),
            'classes_count' => $this->whenCounted('classGroups'),
            'subjects' => $this->whenLoaded('subjects', function () {
                return $this->subjects
                    ->reject(fn (Subject $subject) => Subject::isFrenchLanguage($subject))
                    ->map(fn (Subject $subject) => [
                    'id' => $subject->id,
                    'code' => $subject->code,
                    'name_ar' => $subject->name_ar,
                    'name_fr' => $subject->name_fr,
                ])->values();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
