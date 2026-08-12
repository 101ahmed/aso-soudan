<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'ip',
        'status',
        'mailed_at',
    ];

    protected function casts(): array
    {
        return [
            'mailed_at' => 'datetime',
        ];
    }
}
