<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MediaDecision extends Model
{
    use SoftDeletes;

    public const KINDS = ['decision', 'directive'];

    public const STATUSES = [
        'pending',
        'in_progress',
        'done',
        'delayed',
        'cancelled',
    ];

    protected $fillable = [
        'reference',
        'kind',
        'title_ar',
        'title_fr',
        'details_ar',
        'details_fr',
        'responsible_ar',
        'responsible_fr',
        'department_id',
        'decided_on',
        'due_on',
        'status',
        'show_on_home',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'decided_on' => 'date',
            'due_on' => 'date',
            'show_on_home' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MediaDecision $item) {
            if (blank($item->reference)) {
                $item->reference = 'Q-'.Str::upper(Str::random(6));
            }
            if (blank($item->status)) {
                $item->status = 'pending';
            }
            if (blank($item->kind)) {
                $item->kind = 'decision';
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isOverdue(): bool
    {
        if (! $this->due_on || in_array($this->status, ['done', 'cancelled'], true)) {
            return false;
        }

        return $this->due_on->endOfDay()->lt(now());
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['kind'] ?? null, fn (Builder $q, $kind) => $q->where('kind', $kind))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('title_ar', 'like', $term)
                        ->orWhere('title_fr', 'like', $term)
                        ->orWhere('responsible_ar', 'like', $term)
                        ->orWhere('responsible_fr', 'like', $term)
                        ->orWhere('reference', 'like', $term);
                });
            });
    }
}
