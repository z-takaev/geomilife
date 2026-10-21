<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Promo;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class PromoController extends Controller
{
    public function index(): View
    {
        $promos = Promo::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('start_at')
            ->paginate(config('pagination.per_page.promos'));

        seo()->forRoute(
            routeName: 'promos.index',
            fallbackTitle: __('Акции'),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Акции') => route('promos.index'),
        ]));

        return view('promos.index', compact('promos'));
    }

    public function show(Promo $promo): View
    {
        abort_unless($promo->is_active, 404);

        seo()->forModel(
            seoable: $promo,
            fallbackTitle: $promo->title,
        )
            ->image($promo->getFirstMediaUrl('image'))
            ->type('article')
            ->schema(BreadcrumbSchema::make([
                __('Главная') => route('home'),
                __('Акции') => route('promos.index'),
                $promo->title => route('promos.show', $promo),
            ]));

        return view('promos.show', compact('promo'));
    }
}
