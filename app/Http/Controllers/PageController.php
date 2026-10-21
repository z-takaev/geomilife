<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Page;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->is_active, 404);

        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make($page->title, route('pages.show', $page)),
        );

        seo()->forModel(
            seoable: $page,
            fallbackTitle: $page->title,
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        return view('pages.show', compact('page', 'breadcrumbs'));
    }
}
