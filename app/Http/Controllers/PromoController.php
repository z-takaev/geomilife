<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SeoRoute;
use App\Models\Promo;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class PromoController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function index(): View
    {
        $promos = Promo::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('start_at')
            ->latest('id')
            ->paginate((int) config('pagination.per_page.promos'));
        $seo = $this->seoService->forRoute(SeoRoute::Promos);

        return view('promos.index', compact('promos', 'seo'));
    }

    public function show(Promo $promo): View
    {
        abort_unless($promo->is_active, 404);

        $seo = $this->seoService->forModel(
            seoable: $promo,
            fallbackTitle: $promo->title,
            fallbackDescription: $promo->excerpt ?? __('Специальное предложение на натуральные продукты GeoMiLife.'),
        );

        return view('promos.show', compact('promo', 'seo'));
    }
}
