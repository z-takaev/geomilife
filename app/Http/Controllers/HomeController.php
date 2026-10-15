<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HomeSlide;
use App\Models\Promo;
use App\Models\Review;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function __invoke(): View
    {
        $slides = HomeSlide::cached();
        $promos = Promo::cached();
        $reviews = Review::cached();
        $seo = $this->seoService->forRoute(
            routeName: 'home',
            fallbackTitle: config('app.name'),
        );

        return view('home.index', compact('slides', 'promos', 'reviews', 'seo'));
    }
}
