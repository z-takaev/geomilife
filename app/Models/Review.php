<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

final class Review extends Model
{
    use HasFactory;

    private const string CACHE_KEY = 'latest_reviews';

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

    /**
     * @return Collection<int, self>
     */
    public static function cached(): Collection
    {
        $reviews = Cache::remember(
            self::CACHE_KEY,
            now()->addHours(2),
            static fn (): array => self::query()
                ->select(['id', 'author_name', 'text', 'reviewed_at'])
                ->where('is_active', true)
                ->latest('reviewed_at')
                ->latest('id')
                ->limit(3)
                ->get()
                ->map(static fn (self $review): array => $review->getAttributes())
                ->all(),
        );

        return self::hydrate($reviews);
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
}
