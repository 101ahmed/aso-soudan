<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PresidentialMeeting extends Model
{
    use SoftDeletes;

    public const CLASSIFICATIONS = ['urgent', 'follow_up', 'info'];

    public const STATUSES = ['upcoming', 'held', 'cancelled'];

    protected $fillable = [
        'reference',
        'decision_number',
        'title_ar',
        'title_fr',
        'scheduled_at',
        'location',
        'classification',
        'status',
        'agenda_ar',
        'agenda_fr',
        'minutes_ar',
        'minutes_fr',
        'decisions_ar',
        'decisions_fr',
        'follow_up_ar',
        'follow_up_fr',
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

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'upcoming')
            ->where(function (Builder $inner) {
                $inner->whereNull('scheduled_at')->orWhere('scheduled_at', '>=', now()->startOfDay());
            });
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['classification'] ?? null, fn (Builder $q, $value) => $q->where('classification', $value))
            ->when($filters['status'] ?? null, fn (Builder $q, $value) => $q->where('status', $value))
            ->when(($filters['scope'] ?? null) === 'upcoming', fn (Builder $q) => $q->upcoming())
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('title_ar', 'like', $term)
                        ->orWhere('title_fr', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('decision_number', 'like', $term)
                        ->orWhere('agenda_ar', 'like', $term)
                        ->orWhere('minutes_ar', 'like', $term)
                        ->orWhere('decisions_ar', 'like', $term);
                });
            });
    }
}
