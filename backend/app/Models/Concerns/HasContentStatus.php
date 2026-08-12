<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasContentStatus
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->getTable().'.status', self::STATUS_PUBLISHED);
    }

    public function markPublished(): void
    {
        $this->status = self::STATUS_PUBLISHED;
        if ($this->isFillable('published_at') || array_key_exists('published_at', $this->getAttributes())) {
            $this->published_at = $this->published_at ?? now();
        }
        $this->save();
    }

    public function markPendingReview(): void
    {
        $this->status = self::STATUS_PENDING_REVIEW;
        $this->save();
    }

    public function markArchived(): void
    {
        $this->status = self::STATUS_ARCHIVED;
        $this->save();
    }
}
