<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HomeSlide;
use App\Models\Promo;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function __invoke(): View
    {
        $slides = HomeSlide::cached();
        $promos = Promo::cached();
        $seo = $this->seoService->forRoute(
            routeName: 'home',
            fallbackTitle: __('GeoMiLife — натуральные продукты из Северной Осетии'),
        );

        return view('home.index', compact('slides', 'promos', 'seo'));
    }
}
