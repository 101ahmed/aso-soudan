<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PresidentialDirective extends Model
{
    use SoftDeletes;

    public const CLASSIFICATIONS = ['urgent', 'follow_up', 'info'];

    public const STATUSES = ['sent', 'read', 'in_progress', 'done'];

    protected $fillable = [
        'reference',
        'department_id',
        'assigned_to_user_id',
        'title',
        'body',
        'classification',
        'status',
        'sender_id',
        'read_at',
        'manager_notes',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['classification'] ?? null, fn (Builder $q, $value) => $q->where('classification', $value))
            ->when($filters['status'] ?? null, fn (Builder $q, $value) => $q->where('status', $value))
            ->when($filters['department_id'] ?? null, fn (Builder $q, $value) => $q->where('department_id', $value))
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('body', 'like', $term)
                        ->orWhere('reference', 'like', $term);
                });
            });
    }
}
