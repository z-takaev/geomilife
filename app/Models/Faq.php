<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Traits\HasOrder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class Faq extends Model
{
    use HasFactory, HasOrder;

    private const string CACHE_KEY = 'faqs';

    protected $fillable = [
        'question',
        'answer',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        self::saved(static function (): void {
            Cache::forget(self::CACHE_KEY);
        });

        self::deleted(static function (): void {
            Cache::forget(self::CACHE_KEY);
        });
    }

    /**
     * @return Collection<int, self>
     */
    public static function cached(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addHours(2),
            static fn (): Collection => self::query()
                ->where('is_active', true)
                ->orderBy('order')
                ->get(),
        );
    }
}
