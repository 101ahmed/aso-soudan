<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name_ar',
        'name_fr',
        'description_ar',
        'description_fr',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeOffered($query)
    {
        return $query->where('is_active', true)->where(function ($inner) {
            $inner->whereNull('code')->orWhere('code', '!=', 'FR');
        })->where('name_ar', '!=', 'اللغة الفرنسية');
    }

    public static function isFrenchLanguage(?self $subject): bool
    {
        if (! $subject) {
            return false;
        }

        return $subject->code === 'FR' || $subject->name_ar === 'اللغة الفرنسية';
    }

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(Teacher::class, 'teacher_subject')->withTimestamps();
    }
}
