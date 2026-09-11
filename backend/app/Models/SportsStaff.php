<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportsStaff extends Model
{
    use SoftDeletes;

    public const KINDS = ['technical', 'administrative'];

    protected $table = 'sports_staff';

    protected $fillable = [
        'sports_team_id',
        'is_national',
        'kind',
        'name_ar',
        'name_fr',
        'role_ar',
        'role_fr',
        'photo_path',
        'is_public',
        'sort_order',
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
