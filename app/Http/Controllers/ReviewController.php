<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Review;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class ReviewController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function __invoke(): View
    {
        $reviews = Review::query()
            ->select(['id', 'author_name', 'text', 'reviewed_at'])
            ->where('is_active', true)
            ->latest('reviewed_at')
            ->latest('id')
            ->paginate(config('pagination.per_page.reviews'));

        $seo = $this->seoService->forRoute(
            routeName: 'reviews.index',
            fallbackTitle: __('Отзывы'),
        );

        return view('reviews.index', compact('reviews', 'seo'));
    }
}
