<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class PageController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function show(Page $page): View
    {
        abort_unless($page->is_active, 404);

        $seo = $this->seoService->forModel(
            seoable: $page,
            fallbackTitle: $page->title,
        );

        return view('pages.show', compact('page', 'seo'));
    }
}
