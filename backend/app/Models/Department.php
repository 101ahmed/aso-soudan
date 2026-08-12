<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name_ar',
        'name_fr',
        'description_ar',
        'description_fr',
        'is_active',
        'sort_order',
        'officer_name_ar',
        'officer_name_fr',
        'officer_title_ar',
        'officer_title_fr',
        'officer_bio_ar',
        'officer_bio_fr',
        'officer_email',
        'officer_phone',
        'officer_photo_path',
        'officer_is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'officer_is_public' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
