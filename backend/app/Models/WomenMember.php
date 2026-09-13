<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WomenMember extends Model
{
    use SoftDeletes;

    public const GENDERS = [
        'female',
        'male',
    ];

    public const MARITAL_STATUSES = [
        'single',
        'married',
        'divorced',
        'widowed',
    ];

    protected $fillable = [
        'full_name',
        'gender',
        'residence',
        'marital_status',
        'children_count',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'children_count' => 'integer',
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
                        ->orWhere('residence', 'like', $term)
                        ->orWhere('notes', 'like', $term);
                });
            })
            ->when($filters['gender'] ?? null, fn (Builder $q, $gender) => $q->where('gender', $gender))
            ->when($filters['marital_status'] ?? null, fn (Builder $q, $status) => $q->where('marital_status', $status));
    }
}
