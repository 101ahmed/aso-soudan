<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialVisit extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'home',
        'hospital',
        'condolence',
        'congratulations',
        'ramadan',
        'follow_up',
        'new_family',
        'other',
    ];

    public const STATUSES = [
        'planned',
        'completed',
        'cancelled',
    ];

    protected $fillable = [
        'full_name',
        'phone',
        'place',
        'visit_type',
        'reason',
        'visited_on',
        'visited_at',
        'visitors',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visited_on' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('full_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('place', 'like', $term)
                        ->orWhere('reason', 'like', $term)
                        ->orWhere('visitors', 'like', $term);
                });
            })
            ->when($filters['visit_type'] ?? null, fn (Builder $q, $type) => $q->where('visit_type', $type))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('visited_on', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('visited_on', '<=', $to));
    }
}
