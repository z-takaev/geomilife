<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShippingBox extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'is_active',
        'length', 'width', 'height', 'weight',
    ];

    protected function casts(): array
    {
        return [
            'length' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'weight' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
