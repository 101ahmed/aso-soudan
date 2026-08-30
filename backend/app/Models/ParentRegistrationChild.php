<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentRegistrationChild extends Model
{
    protected $fillable = [
        'parent_registration_id',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'level',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(ParentRegistration::class, 'parent_registration_id');
    }
}
