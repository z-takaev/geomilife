<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_name',
        'text',
        'reviewed_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
