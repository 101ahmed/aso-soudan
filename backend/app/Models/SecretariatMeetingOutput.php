<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SecretariatMeetingOutput extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference',
        'title_ar',
        'title_fr',
        'meeting_on',
        'location',
        'attendees_ar',
        'attendees_fr',
        'agenda_ar',
        'agenda_fr',
        'outputs_ar',
        'outputs_fr',
        'follow_up_ar',
        'follow_up_fr',
        'notes',
        'is_public',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'meeting_on' => 'date',
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SecretariatMeetingOutput $item) {
            if (blank($item->reference)) {
                $item->reference = 'MO-'.Str::upper(Str::random(6));
            }
        });
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query->when($filters['search'] ?? null, function (Builder $q, $search) {
            $term = '%'.trim((string) $search).'%';
            $q->where(function (Builder $inner) use ($term) {
                $inner->where('title_ar', 'like', $term)
                    ->orWhere('title_fr', 'like', $term)
                    ->orWhere('reference', 'like', $term)
                    ->orWhere('location', 'like', $term);
            });
        });
    }
}
