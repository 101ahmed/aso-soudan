<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecretariatDirective extends Model
{
    use SoftDeletes;

    public const CLASSIFICATIONS = ['urgent', 'follow_up', 'info'];

    public const STATUSES = ['sent', 'read', 'in_progress', 'done'];

    protected $fillable = [
        'reference',
        'broadcast_id',
        'is_broadcast',
        'sender_department_id',
        'recipient_department_id',
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
            'is_broadcast' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function senderDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'sender_department_id');
    }

    public function recipientDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'recipient_department_id');
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

    public function scopeForRecipient(Builder $query, int $departmentId): Builder
    {
        return $query->where('recipient_department_id', $departmentId);
    }

    public function scopeFromSender(Builder $query, int $departmentId): Builder
    {
        return $query->where('sender_department_id', $departmentId);
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['classification'] ?? null, fn (Builder $q, $value) => $q->where('classification', $value))
            ->when($filters['status'] ?? null, fn (Builder $q, $value) => $q->where('status', $value))
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
