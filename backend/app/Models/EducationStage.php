<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EducationStage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name_ar',
        'name_fr',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class)->orderBy('sort_order');
    }
}
