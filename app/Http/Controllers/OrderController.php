<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Orders\CancelOrderRequest;
use App\Models\Order;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Services\Orders\OrderLifecycleService;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use App\ViewModel\Orders\OrderViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function index(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Мои заказы'), route('orders.index')),
        );

        seo()->forRoute(
            routeName: 'orders.index',
            fallbackTitle: __('Мои заказы'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $orders = auth()->user()->orders()
            ->with('shipment')
            ->withSum('items', 'quantity')
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);

        $presentations = $orders->getCollection()->mapWithKeys(static fn (Order $order): array => [$order->id => OrderViewModel::data($order)]);

        return view('orders.index', compact('breadcrumbs', 'orders', 'presentations'));
    }

    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 404);

        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Мои заказы'), route('orders.index')),
            BreadcrumbItem::make(
                __('Детали заказа'),
                route('orders.show', $order),
            ),
        );

        seo()->forRoute(
            routeName: 'orders.show',
            fallbackTitle: __('Заказ №:number', ['number' => $order->number]),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $presentation = OrderViewModel::data($order);

        return view('orders.show', compact('order', 'breadcrumbs', 'presentation'));
    }

    public function cancel(Request $request, Order $order, OrderLifecycleService $lifecycle): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->user_id === $user->id, 404);
        abort_unless($order->status->isNew() && $order->cancellation_state === null, 403);

        $reason = app(CancelOrderRequest::class)->validated('reason') ?? __('Отмена покупателем');
        $lifecycle->cancel($order, $user, $reason, onlyIfNew: true);

        return redirect()->route('orders.show', $order)->with('order_message', __('Запрос отмены принят. Состояние возврата отображается в заказе.'));
    }
}
