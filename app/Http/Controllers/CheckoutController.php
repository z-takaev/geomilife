<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class CheckoutController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function __invoke(): View
    {
        $seo = $this->seoService->forRoute(
            routeName: 'checkout.index',
            fallbackTitle: __('Оформление заказа'),
        );

        return view('checkout.index', compact('seo'));
    }
}
