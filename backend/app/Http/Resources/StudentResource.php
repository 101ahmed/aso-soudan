<?php

namespace App\Http\Resources;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Student */
class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'status' => $this->status,
            'notes' => $this->notes,
            'academic_year_id' => $this->academic_year_id,
            'education_stage_id' => $this->education_stage_id,
            'level_id' => $this->level_id,
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear?->only(['id', 'name'])),
            'education_stage' => $this->whenLoaded('educationStage', fn () => $this->educationStage?->only(['id', 'code', 'name_ar', 'name_fr'])),
            'level' => $this->whenLoaded('level', fn () => $this->level?->only(['id', 'code', 'name_ar', 'name_fr', 'education_stage_id'])),
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
