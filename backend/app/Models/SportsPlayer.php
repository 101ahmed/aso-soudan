<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportsPlayer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sports_team_id',
        'is_national',
        'name_ar',
        'name_fr',
        'position',
        'number',
        'birth_date',
        'photo_path',
        'appearances',
        'goals',
        'assists',
        'yellow_cards',
        'red_cards',
        'is_public',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_national' => 'boolean',
            'is_public' => 'boolean',
            'birth_date' => 'date',
            'number' => 'integer',
            'appearances' => 'integer',
            'goals' => 'integer',
            'assists' => 'integer',
            'yellow_cards' => 'integer',
            'red_cards' => 'integer',
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

    public function scopeNational(Builder $query): Builder
    {
        return $query->where('is_national', true);
    }
}
