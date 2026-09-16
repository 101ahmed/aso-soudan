<?php

namespace App\Models;

use App\Support\MapUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouncilMeeting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'council_code',
        'reference',
        'title_ar',
        'title_fr',
        'scheduled_at',
        'location',
        'map_url',
        'status',
        'agenda_ar',
        'agenda_fr',
        'minutes_ar',
        'minutes_fr',
        'visibility',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CouncilMeetingAttendance::class);
    }

    public function scopeForCouncil($query, string $code = 'shura')
    {
        return $query->where('council_code', $code);
    }

    public function scopePublicVisible($query)
    {
        return $query->where('visibility', 'public');
    }

    public function resolvedMapUrl(): ?string
    {
        return MapUrl::normalize($this->map_url, $this->location);
    }
}
