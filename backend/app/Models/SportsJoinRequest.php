<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SportsJoinRequest extends Model
{
    use SoftDeletes;

    public const STATUSES = ['new', 'reviewing', 'accepted', 'rejected'];

    protected $fillable = [
        'reference',
        'full_name',
        'birth_date',
        'email',
        'phone',
        'city',
        'age_category',
        'position',
        'sports_team_id',
        'for_national',
        'message',
        'status',
        'admin_notes',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'for_national' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SportsJoinRequest $item) {
            if (blank($item->reference)) {
                $item->reference = 'SP-'.Str::upper(Str::random(6));
            }
            if (blank($item->status)) {
                $item->status = 'new';
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(SportsTeam::class, 'sports_team_id');
    }
}
