<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ExternalContactRequest extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'reviewing', 'contacted', 'closed', 'rejected'];

    protected $fillable = [
        'reference',
        'applicant_name',
        'applicant_phone',
        'applicant_email',
        'applicant_organization',
        'partner_id',
        'partner_name',
        'reason',
        'status',
        'admin_notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExternalContactRequest $request) {
            if (blank($request->reference)) {
                $request->reference = 'X-'.Str::upper(Str::random(6));
            }
            if (blank($request->status)) {
                $request->status = 'pending';
            }
            if (blank($request->submitted_at)) {
                $request->submitted_at = now();
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(ExternalPartner::class, 'partner_id');
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('applicant_name', 'like', $term)
                        ->orWhere('applicant_phone', 'like', $term)
                        ->orWhere('applicant_email', 'like', $term)
                        ->orWhere('applicant_organization', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('reason', 'like', $term)
                        ->orWhere('partner_name', 'like', $term);
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status));
    }
}
