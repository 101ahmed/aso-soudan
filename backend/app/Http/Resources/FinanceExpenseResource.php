<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\FinanceExpense */
class FinanceExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'occurred_on' => $this->occurred_on?->toDateString(),
            'amount' => (float) $this->amount,
            'category' => $this->category,
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name_ar' => $this->department->name_ar,
                'name_fr' => $this->department->name_fr,
            ]),
            'project_ar' => $this->project_ar,
            'project_fr' => $this->project_fr,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
