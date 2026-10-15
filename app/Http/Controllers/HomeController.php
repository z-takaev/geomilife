<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HomeSlide;
use App\Models\Promo;
use App\Models\Review;
use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\ProductRepository;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __construct(
        private readonly SeoService $seoService,
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function __invoke(): View
    {
        $slides = HomeSlide::cached();
        $promos = Promo::cached();
        $reviews = Review::cached();
        $featuredCategories = $this->categoryRepository->getFeatured();
        $hitProducts = $this->productRepository->getHits();
        $newProducts = $this->productRepository->getNewProducts();
        $discountedProducts = $this->productRepository->getDiscountedProducts();
        $seo = $this->seoService->forRoute(
            routeName: 'home',
            fallbackTitle: config('app.name'),
        );

        return view('home.index', compact(
            'slides',
            'promos',
            'reviews',
            'featuredCategories',
            'hitProducts',
            'newProducts',
            'discountedProducts',
            'seo',
        ));
    }
}
