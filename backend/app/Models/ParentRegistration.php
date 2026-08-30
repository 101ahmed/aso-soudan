<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentRegistration extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'active', 'archived'];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'city',
        'address',
        'notes',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function children(): HasMany
    {
        return $this->hasMany(ParentRegistrationChild::class);
    }
}
