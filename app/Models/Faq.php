<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class Faq extends Model
{
    use HasFactory;

    protected $fillable = [
        'question', 'answer',
        'order', 'is_active',
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
        self::creating(static function (self $faq): void {
            if ($faq->order !== null) {
                return;
            }

            $faq->order = ((int) self::query()->max('order')) + 1;
        });

        self::saved(static function (): void {
            Cache::forget((string) config('cache.items.faqs.key'));
        });

        self::deleted(static function (): void {
            Cache::forget((string) config('cache.items.faqs.key'));
        });
    }

    public static function cached(): Collection
    {
        return Cache::remember(
            (string) config('cache.items.faqs.key'),
            (int) config('cache.items.faqs.ttl'),
            static fn (): Collection => self::query()
                ->where('is_active', true)
                ->orderBy('order')
                ->get(),
        );
    }
}
