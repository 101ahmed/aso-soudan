<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HelpRequest extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'financial',
        'food',
        'housing',
        'admin_papers',
        'health',
        'family',
        'ramadan',
        'emergency',
        'other',
    ];

    public const STATUSES = [
        'pending',
        'reviewing',
        'approved',
        'in_progress',
        'completed',
        'rejected',
    ];

    protected $fillable = [
        'reference',
        'full_name',
        'phone',
        'email',
        'city',
        'help_type',
        'details',
        'family_size',
        'status',
        'admin_notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'family_size' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (HelpRequest $request) {
            if (blank($request->reference)) {
                $request->reference = 'M-'.Str::upper(Str::random(6));
            }
            if (blank($request->status)) {
                $request->status = 'pending';
            }
            if (blank($request->submitted_at)) {
                $request->submitted_at = now();
            }
        });
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('full_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('reference', 'like', $term);
                });
            })
            ->when($filters['help_type'] ?? null, fn (Builder $q, $type) => $q->where('help_type', $type))
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status));
    }
}
