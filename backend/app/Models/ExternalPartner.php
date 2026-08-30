<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalPartner extends Model
{
    use SoftDeletes;

    public const TYPES = ['association', 'institution', 'municipality', 'cultural', 'other'];

    public const STATUSES = ['prospect', 'active', 'paused', 'ended'];

    protected $fillable = [
        'name_ar',
        'name_fr',
        'type',
        'city',
        'website',
        'email',
        'phone',
        'description_ar',
        'description_fr',
        'partnership_status',
        'is_public',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ExternalDocument::class, 'partner_id');
    }

    public function contactRequests(): HasMany
    {
        return $this->hasMany(ExternalContactRequest::class, 'partner_id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $term = '%'.trim((string) $search).'%';
                $q->where(function (Builder $inner) use ($term) {
                    $inner->where('name_ar', 'like', $term)
                        ->orWhere('name_fr', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($filters['type'] ?? null, fn (Builder $q, $type) => $q->where('type', $type))
            ->when($filters['partnership_status'] ?? null, fn (Builder $q, $status) => $q->where('partnership_status', $status));
    }
}
