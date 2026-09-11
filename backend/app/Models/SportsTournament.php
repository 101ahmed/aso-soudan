<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportsTournament extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sports_team_id',
        'is_national',
        'title_ar',
        'title_fr',
        'season',
        'location',
        'ranking',
        'notes_ar',
        'notes_fr',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_national' => 'boolean',
            'is_public' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(SportsTeam::class, 'sports_team_id');
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }
}
