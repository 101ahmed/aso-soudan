<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceRevenue extends Model
{
    use SoftDeletes;

    public const SOURCES = [
        'membership',
        'donation',
        'individual',
        'project_support',
        'activity',
        'other',
    ];

    protected $fillable = [
        'occurred_on',
        'amount',
        'source',
        'title_ar',
        'title_fr',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('occurred_on', $year);
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['year'] ?? null, fn (Builder $q, $year) => $q->forYear((int) $year))
            ->when($filters['source'] ?? null, fn (Builder $q, $source) => $q->where('source', $source))
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('title_ar', 'like', $term)
                        ->orWhere('title_fr', 'like', $term)
                        ->orWhere('notes', 'like', $term);
                });
            });
    }
}
