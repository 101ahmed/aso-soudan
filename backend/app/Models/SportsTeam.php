<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportsTeam extends Model
{
    use SoftDeletes;

    public const AGE_CATEGORIES = [
        'u7', 'u9', 'u11', 'u13', 'u15', 'u17', 'u19', 'seniors', 'women', 'veterans',
    ];

    protected $fillable = [
        'name_ar',
        'name_fr',
        'age_category',
        'sport',
        'coach_ar',
        'coach_fr',
        'manager_ar',
        'manager_fr',
        'photo_path',
        'ranking',
        'points',
        'played',
        'wins',
        'draws',
        'losses',
        'goals_for',
        'goals_against',
        'notes_ar',
        'notes_fr',
        'is_public',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'ranking' => 'integer',
            'points' => 'integer',
            'played' => 'integer',
            'wins' => 'integer',
            'draws' => 'integer',
            'losses' => 'integer',
            'goals_for' => 'integer',
            'goals_against' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function players(): HasMany
    {
        return $this->hasMany(SportsPlayer::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(SportsStaff::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(SportsMatch::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(SportsTraining::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(SportsTournament::class);
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(SportsJoinRequest::class);
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }
}
