<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MediaCenterItem extends Model
{
    use HasContentStatus, SoftDeletes;

    public const KINDS = [
        'official',
        'statement',
        'coverage',
        'conference',
        'interview',
    ];

    protected $fillable = [
        'slug',
        'kind',
        'title_ar',
        'title_fr',
        'content_ar',
        'content_fr',
        'source_ar',
        'source_fr',
        'person_ar',
        'person_fr',
        'location_ar',
        'location_fr',
        'external_url',
        'image_path',
        'occurred_on',
        'status',
        'published_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (MediaCenterItem $item) {
            if (blank($item->slug) && filled($item->title_fr)) {
                $item->slug = Str::slug($item->title_fr).'-'.Str::lower(Str::random(5));
            }
            if (blank($item->kind)) {
                $item->kind = 'official';
            }
            if (blank($item->status)) {
                $item->status = self::STATUS_DRAFT;
            }
        });
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getRouteKeyName(): string
    {
        return 'id';
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
                        ->orWhere('source_ar', 'like', $term)
                        ->orWhere('source_fr', 'like', $term)
                        ->orWhere('person_ar', 'like', $term)
                        ->orWhere('person_fr', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            });
    }
}
