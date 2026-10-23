<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewSourceEnum;
use Illuminate\Database\Eloquent\Builder;
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
        'rating',
        'source',
        'reviewed_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'source' => ReviewSourceEnum::class,
            'reviewed_at' => 'date',
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

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
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
                ->select(['id', 'author_name', 'text', 'rating', 'source', 'reviewed_at'])
                ->active()
                ->latest('reviewed_at')
                ->latest('id')
                ->limit(3)
                ->get(),
        );
    }
}
