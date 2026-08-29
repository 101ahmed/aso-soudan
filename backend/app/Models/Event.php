<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasContentStatus, SoftDeletes;

    public const TYPES = ['seminar', 'lecture', 'activity'];

    protected $fillable = [
        'type',
        'title_ar',
        'title_fr',
        'description_ar',
        'description_fr',
        'slug',
        'department_id',
        'location',
        'location_ar',
        'location_fr',
        'starts_at',
        'ends_at',
        'image_path',
        'audience',
        'status',
        'show_on_secretariat',
        'show_on_home',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'published_at' => 'datetime',
            'show_on_secretariat' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event) {
            if (blank($event->slug) && filled($event->title_fr)) {
                $event->slug = Str::slug($event->title_fr).'-'.Str::lower(Str::random(5));
            }
            if (blank($event->location) && (filled($event->location_fr) || filled($event->location_ar))) {
                $event->location = $event->location_fr ?: $event->location_ar;
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
