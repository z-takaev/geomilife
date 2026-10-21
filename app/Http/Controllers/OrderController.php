<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class OrderController extends Controller
{
    public function index(): View
    {
        seo()->forRoute(
            routeName: 'orders.index',
            fallbackTitle: __('Мои заказы'),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Профиль') => route('profile.show'),
            __('Мои заказы') => route('orders.index'),
        ]));

        return view('orders.index');
    }

    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 404);

        seo()->forRoute(
            routeName: 'orders.show',
            fallbackTitle: __('Заказ №:number', ['number' => $order->number]),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Профиль') => route('profile.show'),
            __('Мои заказы') => route('orders.index'),
            __('Заказ №:number', ['number' => $order->number]) => route('orders.show', $order),
        ]));

        return view('orders.show', compact('order'));
    }
}
