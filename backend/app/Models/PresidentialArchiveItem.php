<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PresidentialArchiveItem extends Model
{
    use SoftDeletes;

    public const CATEGORIES = [
        'decision',
        'directive',
        'agreement',
        'minutes',
        'correspondence',
        'report',
    ];

    protected $fillable = [
        'category',
        'title_ar',
        'title_fr',
        'body_ar',
        'body_fr',
        'decision_number',
        'document_date',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['category'] ?? null, fn (Builder $q, $value) => $q->where('category', $value))
            ->when($filters['decision_number'] ?? null, function (Builder $q, $value) {
                $term = '%'.trim((string) $value).'%';
                $q->where('decision_number', 'like', $term);
            })
            ->when($filters['date_from'] ?? null, fn (Builder $q, $value) => $q->whereDate('document_date', '>=', $value))
            ->when($filters['date_to'] ?? null, fn (Builder $q, $value) => $q->whereDate('document_date', '<=', $value))
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('title_ar', 'like', $term)
                        ->orWhere('title_fr', 'like', $term)
                        ->orWhere('body_ar', 'like', $term)
                        ->orWhere('body_fr', 'like', $term)
                        ->orWhere('decision_number', 'like', $term);
                });
            });
    }
}
