<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecretariatMessage extends Model
{
    use SoftDeletes;

    public const STATUSES = ['new', 'read', 'replied', 'archived'];

    protected $fillable = [
        'department_id',
        'sender_name',
        'sender_email',
        'sender_phone',
        'subject',
        'body',
        'status',
        'admin_notes',
        'ip',
        'read_at',
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

    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('sender_name', 'like', $term)
                        ->orWhere('sender_email', 'like', $term)
                        ->orWhere('sender_phone', 'like', $term)
                        ->orWhere('subject', 'like', $term)
                        ->orWhere('body', 'like', $term);
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status));
    }
}
