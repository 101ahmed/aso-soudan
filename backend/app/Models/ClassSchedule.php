<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSchedule extends Model
{
    protected $fillable = [
        'class_group_id',
        'weekday',
        'starts_at',
        'ends_at',
        'room',
    ];

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    public function startsAt(): string
    {
        return substr((string) $this->starts_at, 0, 5);
    }

    public function endsAt(): string
    {
        return substr((string) $this->ends_at, 0, 5);
    }
}
