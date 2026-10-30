<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class PromoController extends Controller
{
    public function index(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Акции'), route('promos.index')),
        );

        seo()->forRoute(
            routeName: 'promos.index',
            fallbackTitle: __('Акции'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $promos = Promo::query()
            ->with('media')
            ->current()
            ->latest('start_at')
            ->paginate(config('pagination.per_page.promos'));

        return view('promos.index', compact('promos', 'breadcrumbs'));
    }

    public function show(Promo $promo): View
    {
        abort_unless($promo->isAvailable(), 404);

        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Акции'), route('promos.index')),
            BreadcrumbItem::make($promo->title, route('promos.show', $promo)),
        );

        seo()->forModel(
            seoable: $promo,
            fallbackTitle: $promo->title,
        )
            ->image($promo->getFirstMediaUrl('image'))
            ->type('article')
            ->schema(BreadcrumbSchema::make($breadcrumbs));

        return view('promos.show', compact('promo', 'breadcrumbs'));
    }
}
