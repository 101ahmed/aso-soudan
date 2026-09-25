<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisitDay extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'visited_on',
        'unique_visitors',
        'page_views',
    ];

    protected function casts(): array
    {
        return [
            'visited_on' => 'date',
            'unique_visitors' => 'integer',
            'page_views' => 'integer',
        ];
    }
}
