<?php

namespace App\Http\Resources;

use App\Models\LessonPreparation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LessonPreparation */
class LessonPreparationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher_id' => $this->teacher_id,
            'subject_id' => $this->subject_id,
            'level_id' => $this->level_id,
            'lesson_date' => $this->lesson_date?->toDateString(),
            'title' => $this->title,
            'unit' => $this->unit,
            'objectives' => $this->objectives,
            'skills' => $this->skills,
            'concepts' => $this->concepts,
            'intro' => $this->intro,
            'explanation' => $this->explanation,
            'activities' => $this->activities,
            'group_work' => $this->group_work,
            'assessment' => $this->assessment,
            'conclusion' => $this->conclusion,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'subject' => $this->whenLoaded('subject', fn () => $this->subject ? [
                'id' => $this->subject->id,
                'code' => $this->subject->code,
                'name_ar' => $this->subject->name_ar,
                'name_fr' => $this->subject->name_fr,
            ] : null),
            'level' => $this->whenLoaded('level', fn () => $this->level ? [
                'id' => $this->level->id,
                'code' => $this->level->code,
                'name_ar' => $this->level->name_ar,
                'name_fr' => $this->level->name_fr,
            ] : null),
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id' => $this->teacher->id,
                'full_name' => $this->teacher->full_name,
            ] : null),
        ];
    }
}
