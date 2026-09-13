<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AcademicExam */
class AcademicExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'period' => $this->period,
            'exam_date' => $this->exam_date?->toDateString(),
            'max_score' => (float) $this->max_score,
            'pass_score' => (float) $this->pass_score,
            'notes' => $this->notes,
            'academic_year_id' => $this->academic_year_id,
            'level_id' => $this->level_id,
            'subject_id' => $this->subject_id,
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear?->only(['id', 'name'])),
            'level' => $this->whenLoaded('level', fn () => $this->level?->only(['id', 'code', 'name_ar', 'name_fr'])),
            'subject' => $this->whenLoaded('subject', fn () => $this->subject?->only(['id', 'code', 'name_ar', 'name_fr'])),
            'grades_count' => $this->whenCounted('grades'),
            'can_grade' => $this->when(isset($this->can_grade), $this->can_grade),
            'can_manage' => $this->when(isset($this->can_manage), $this->can_manage),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
