<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\FinanceRevenue */
class FinanceRevenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'occurred_on' => $this->occurred_on?->toDateString(),
            'amount' => (float) $this->amount,
            'source' => $this->source,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'notes' => $this->notes,
            'recorded_by' => $this->recorded_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
