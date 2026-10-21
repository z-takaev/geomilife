<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Review;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class ReviewController extends Controller
{
    public function __invoke(): View
    {
        $reviews = Review::query()
            ->select(['id', 'author_name', 'text', 'reviewed_at'])
            ->where('is_active', true)
            ->latest('reviewed_at')
            ->latest('id')
            ->paginate(config('pagination.per_page.reviews'));

        seo()->forRoute(
            routeName: 'reviews.index',
            fallbackTitle: __('Отзывы'),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Отзывы') => route('reviews.index'),
        ]));

        return view('reviews.index', compact('reviews'));
    }
}
