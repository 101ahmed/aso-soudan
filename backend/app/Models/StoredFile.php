<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoredFile extends Model
{
    protected $fillable = [
        'uuid',
        'collection',
        'original_name',
        'mime',
        'size',
        'payload',
        'legacy_path',
    ];

    protected $hidden = [
        'payload',
    ];
}
