<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentSurveyResponse extends Model
{
    protected $fillable = [
        'parent_survey_id',
        'parent_name',
        'email',
        'phone',
        'answers',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(ParentSurvey::class, 'parent_survey_id');
    }
}
