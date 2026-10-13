<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SeoRoute;
use App\Models\HomeSlide;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function __invoke(): View
    {
        $slides = HomeSlide::cached();
        $seo = $this->seoService->forRoute(SeoRoute::Home);

        return view('home.index', compact('slides', 'seo'));
    }
}
