<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }
}
