<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportsTraining extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sports_team_id',
        'weekday',
        'starts_at',
        'ends_at',
        'location',
        'notes_ar',
        'notes_fr',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(SportsTeam::class, 'sports_team_id');
    }
}
