<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Services\Cart\CartService;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class CheckoutController extends Controller
{
    public function __invoke(CartService $cart): View
    {
        abort_if($cart->count() === 0, 404);

        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Оформление заказа'), route('checkout')),
        );

        seo()->forRoute(
            routeName: 'checkout',
            fallbackTitle: __('Оформление заказа'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        return view('checkout.index', compact('breadcrumbs'));
    }

    public function orderSuccess(): View
    {
        $title = __('Заказ успешно оформлен');
        $description = __('Подтверждение отправлено на указанный email.');

        seo()->forRoute(
            routeName: 'checkout.success',
            fallbackTitle: $title,
        );

        return view('checkout.result', compact('title', 'description'));
    }

    public function paymentSuccess(): View
    {
        $title = __('Платёж завершён');
        $description = __('Статус заказа обновится автоматически после подтверждения банка.');

        seo()->forRoute(
            routeName: 'payment.success',
            fallbackTitle: $title,
        );

        return view('checkout.result', compact('title', 'description'));
    }

    public function paymentFail(): View
    {
        $title = __('Оплата не завершена');
        $description = (string) session('order_message', __('Заказ сохранён. Свяжитесь с нами для уточнения оплаты.'));

        seo()->forRoute(
            routeName: 'payment.fail',
            fallbackTitle: $title,
        );

        return view('checkout.result', compact('title', 'description'));
    }
}
