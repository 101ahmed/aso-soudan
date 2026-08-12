<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouncilMember extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_FORMER = 'former';

    public const STATUS_SUSPENDED = 'suspended';

    public const POSITIONS = [
        'president',
        'vice_president',
        'secretary',
        'member',
    ];

    protected $fillable = [
        'council_code',
        'user_id',
        'first_name',
        'last_name',
        'photo_path',
        'email',
        'phone',
        'position_code',
        'position_ar',
        'position_fr',
        'bio_ar',
        'bio_fr',
        'started_at',
        'ended_at',
        'status',
        'is_public',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CouncilMeetingAttendance::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function scopePublicVisible($query)
    {
        return $query
            ->where('is_public', true)
            ->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForCouncil($query, string $code = 'shura')
    {
        return $query->where('council_code', $code);
    }
}
