<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportsMatch extends Model
{
    use SoftDeletes;

    public const STATUSES = ['scheduled', 'played', 'cancelled'];

    protected $fillable = [
        'sports_team_id',
        'is_national',
        'competition_ar',
        'competition_fr',
        'opponent_ar',
        'opponent_fr',
        'played_on',
        'venue',
        'is_home',
        'goals_for',
        'goals_against',
        'status',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_national' => 'boolean',
            'is_home' => 'boolean',
            'is_public' => 'boolean',
            'played_on' => 'date',
            'goals_for' => 'integer',
            'goals_against' => 'integer',
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
