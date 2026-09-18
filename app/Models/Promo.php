<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $fillable = [
        'name', 'slug',
        'excerpt', 'content',
        'is_active', 'start_at', 'end_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_at'  => 'datetime',
        'end_at'    => 'datetime',
    ];
}
