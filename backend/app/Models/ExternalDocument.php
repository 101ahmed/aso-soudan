<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExternalDocument extends Model
{
    use SoftDeletes;

    public const CATEGORIES = ['agreement', 'letter', 'report', 'representation', 'other'];

    protected $fillable = [
        'partner_id',
        'title_ar',
        'title_fr',
        'category',
        'file_path',
        'original_name',
        'mime',
        'size',
        'is_public',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'size' => 'integer',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(ExternalPartner::class, 'partner_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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
                    $inner->where('title_ar', 'like', $term)
                        ->orWhere('title_fr', 'like', $term)
                        ->orWhere('original_name', 'like', $term);
                });
            })
            ->when($filters['category'] ?? null, fn (Builder $q, $cat) => $q->where('category', $cat))
            ->when($filters['partner_id'] ?? null, fn (Builder $q, $id) => $q->where('partner_id', $id));
    }
}
