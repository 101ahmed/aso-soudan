<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class News extends Model
{
    use HasContentStatus, SoftDeletes;

    protected $fillable = [
        'title_ar',
        'title_fr',
        'content_ar',
        'content_fr',
        'slug',
        'image_path',
        'author_id',
        'department_id',
        'status',
        'is_featured',
        'show_on_home',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'show_on_home' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (News $news) {
            if (blank($news->slug) && filled($news->title_fr)) {
                $news->slug = Str::slug($news->title_fr).'-'.Str::random(5);
            }
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
