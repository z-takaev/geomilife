<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Cdek\CdekPrintStateEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'uuid',
        'state',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'state' => CdekPrintStateEnum::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
