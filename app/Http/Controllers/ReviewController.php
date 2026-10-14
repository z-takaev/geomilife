<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class ReviewController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function __invoke(): View
    {
        $seo = $this->seoService->forRoute(
            routeName: 'reviews.index',
            fallbackTitle: __('Отзывы'),
        );

        return view('reviews.index', compact('seo'));
    }
}
