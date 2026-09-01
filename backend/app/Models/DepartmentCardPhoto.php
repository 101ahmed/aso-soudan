<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentCardPhoto extends Model
{
    protected $fillable = [
        'department_id',
        'role',
        'mime',
        'payload',
    ];

    protected $hidden = [
        'payload',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
