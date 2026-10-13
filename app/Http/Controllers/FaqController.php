<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SeoRoute;
use App\Models\Faq;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class FaqController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function index(): View
    {
        $faqs = Faq::cached();
        $seo = $this->seoService->forRoute(SeoRoute::Faqs);

        return view('faqs.index', compact('faqs', 'seo'));
    }
}
