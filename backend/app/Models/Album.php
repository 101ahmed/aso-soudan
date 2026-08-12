<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Album extends Model
{
    use HasContentStatus, SoftDeletes;

    protected $fillable = [
        'title_ar',
        'title_fr',
        'slug',
        'description_ar',
        'description_fr',
        'event_id',
        'department_id',
        'cover_path',
        'status',
        'is_published',
        'show_on_home',
        'show_on_gallery',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_on_home' => 'boolean',
            'show_on_gallery' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Album $album) {
            $album->is_published = $album->status === 'published';
            if ($album->status === 'published' && blank($album->published_at)) {
                $album->published_at = now();
            }

            if (blank($album->slug)) {
                $base = Str::slug($album->title_fr ?: $album->title_ar) ?: 'album';
                $slug = $base;
                $i = 1;
                while (static::query()
                    ->where('slug', $slug)
                    ->when($album->exists, fn ($q) => $q->where('id', '!=', $album->id))
                    ->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $album->slug = $slug;
            }
        });
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return $this->where($field, $value)->firstOrFail();
        }

        if (ctype_digit((string) $value)) {
            return $this->whereKey($value)->firstOrFail();
        }

        return $this->where('slug', $value)->firstOrFail();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class)->orderBy('sort_order');
    }
}
