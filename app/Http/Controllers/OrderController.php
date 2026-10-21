<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class OrderController extends Controller
{
    public function index(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Профиль'), route('profile.show')),
            BreadcrumbItem::make(__('Мои заказы'), route('orders.index')),
        );

        seo()->forRoute(
            routeName: 'orders.index',
            fallbackTitle: __('Мои заказы'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        return view('orders.index', compact('breadcrumbs'));
    }

    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 404);

        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Профиль'), route('profile.show')),
            BreadcrumbItem::make(__('Мои заказы'), route('orders.index')),
            BreadcrumbItem::make(
                __('Заказ №:number', ['number' => $order->number]),
                route('orders.show', $order),
            ),
        );

        seo()->forRoute(
            routeName: 'orders.show',
            fallbackTitle: __('Заказ №:number', ['number' => $order->number]),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        return view('orders.show', compact('order', 'breadcrumbs'));
    }
}
