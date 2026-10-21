<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HomeSlide;
use App\Models\Promo;
use App\Models\Review;
use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\ProductRepository;
use App\Support\Seo\Schemas\OrganizationSchema;
use App\Support\Seo\Schemas\WebSiteSchema;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function __invoke(): View
    {
        seo()->forRoute(
            routeName: 'home',
            fallbackTitle: config('app.name'),
        )
            ->schema(OrganizationSchema::make(settings()))
            ->schema(WebSiteSchema::make());

        $slides = HomeSlide::cached();
        $promos = Promo::cached();
        $reviews = Review::cached();
        $featuredCategories = $this->categoryRepository->getFeatured();

        [
            'hitProducts' => $hitProducts,
            'newProducts' => $newProducts,
            'discountedProducts' => $discountedProducts,
        ] = $this->productRepository->getHomeProducts();

        return view('home.index', compact(
            'slides',
            'promos',
            'reviews',
            'featuredCategories',
            'hitProducts',
            'newProducts',
            'discountedProducts',
        ));
    }
}
