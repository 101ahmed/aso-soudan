<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ParentSurvey */
class ParentSurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $includeInternal = (bool) $request->user();

        return [
            'id' => $this->id,
            'title_ar' => $this->title_ar,
            'title_fr' => $this->title_fr,
            'details_ar' => $this->details_ar,
            'details_fr' => $this->details_fr,
            'form_url' => $this->form_url,
            'questions' => $this->questionList(),
            'status' => $this->when($includeInternal, $this->status),
            'published_at' => $this->published_at?->toIso8601String(),
            'responses_count' => $this->whenCounted('responses', $this->responses_count),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
