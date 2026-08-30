<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\MediaDecision */
class MediaDecisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'kind' => $this->kind,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'details_ar' => $this->details_ar,
            'details_fr' => $this->details_fr,
            'responsible_ar' => $this->when($request->user(), $this->responsible_ar),
            'responsible_fr' => $this->when($request->user(), $this->responsible_fr),
            'department_id' => $this->when($request->user(), $this->department_id),
            'department' => $this->when($request->user() && $this->relationLoaded('department') && $this->department, fn () => [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name_ar' => $this->department->name_ar,
                'name_fr' => $this->department->name_fr,
            ]),
            'decided_on' => $this->decided_on?->toDateString(),
            'due_on' => $this->when($request->user(), $this->due_on?->toDateString()),
            'status' => $this->when($request->user(), $this->status),
            'overdue' => $this->when($request->user(), $this->isOverdue()),
            'show_on_home' => $this->when($request->user(), (bool) $this->show_on_home),
            'notes' => $this->when($request->user(), $this->notes),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
