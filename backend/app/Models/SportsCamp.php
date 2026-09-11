<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportsCamp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title_ar',
        'title_fr',
        'starts_on',
        'ends_on',
        'location',
        'notes_ar',
        'notes_fr',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_public' => 'boolean',
        ];
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }
}
