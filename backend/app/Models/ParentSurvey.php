<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentSurvey extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'published', 'closed'];

    protected $fillable = [
        'title_ar',
        'title_fr',
        'details_ar',
        'details_fr',
        'form_url',
        'questions',
        'status',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ParentSurveyResponse::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function questionList(): array
    {
        $questions = $this->questions;
        if (! is_array($questions)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $questions)));
    }
}
