<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceDocument extends Model
{
    public const KIND_GENERAL_REPORT = 'general_report';

    public const KIND_SUBSCRIPTIONS = 'subscriptions_announcement';

    public const KINDS = [
        self::KIND_GENERAL_REPORT,
        self::KIND_SUBSCRIPTIONS,
    ];

    public const DEFAULTS = [
        self::KIND_GENERAL_REPORT => [
            'title_ar' => 'تقرير مالي عام',
            'title_fr' => 'Rapport financier public',
        ],
        self::KIND_SUBSCRIPTIONS => [
            'title_ar' => 'إعلان الاشتراكات',
            'title_fr' => 'Annonce des cotisations',
        ],
    ];

    protected $fillable = [
        'kind',
        'title_ar',
        'title_fr',
        'body_ar',
        'body_fr',
        'file_path',
        'original_name',
        'mime',
        'size',
        'is_published',
        'published_at',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function hasPublicContent(): bool
    {
        return filled($this->file_path)
            || filled($this->body_ar)
            || filled($this->body_fr);
    }

    public static function ensureSlots(): Collection
    {
        foreach (self::KINDS as $kind) {
            self::query()->firstOrCreate(
                ['kind' => $kind],
                [
                    'title_ar' => self::DEFAULTS[$kind]['title_ar'],
                    'title_fr' => self::DEFAULTS[$kind]['title_fr'],
                    'is_published' => false,
                ]
            );
        }

        return self::query()
            ->orderByRaw("CASE kind WHEN '".self::KIND_GENERAL_REPORT."' THEN 1 ELSE 2 END")
            ->get();
    }
}
