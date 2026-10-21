<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class CheckoutController extends Controller
{
    public function __invoke(Request $request): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Оформление заказа'), route('checkout.index')),
        );

        seo()->forRoute(
            routeName: 'checkout.index',
            fallbackTitle: __('Оформление заказа'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $customer = $request->user();
        $customer?->loadMissing('city');
        $deliveryAddress = $customer?->addresses()
            ->with('city')
            ->where('is_default', true)
            ->first();

        return view('checkout.index', compact('customer', 'deliveryAddress', 'breadcrumbs'));
    }
}
