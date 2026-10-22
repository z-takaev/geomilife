<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Review;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class ReviewController extends Controller
{
    public function __invoke(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Отзывы'), route('reviews.index')),
        );

        seo()->forRoute(
            routeName: 'reviews.index',
            fallbackTitle: __('Отзывы'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $reviews = Review::query()
            ->select(['id', 'author_name', 'text', 'rating', 'source', 'reviewed_at'])
            ->where('is_active', true)
            ->latest('reviewed_at')
            ->latest('id')
            ->paginate(config('pagination.per_page.reviews'));

        return view('reviews.index', compact('reviews', 'breadcrumbs'));
    }
}
