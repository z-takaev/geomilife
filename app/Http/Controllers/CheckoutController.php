<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class CheckoutController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function __invoke(Request $request): View
    {
        $seo = $this->seoService->forRoute(
            routeName: 'checkout.index',
            fallbackTitle: __('Оформление заказа'),
        );
        $customer = $request->user();
        $customer?->loadMissing('city');
        $deliveryAddress = $customer?->addresses()
            ->with('city')
            ->where('is_default', true)
            ->first();

        return view('checkout.index', compact('customer', 'deliveryAddress', 'seo'));
    }
}
