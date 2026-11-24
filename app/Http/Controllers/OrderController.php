<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\CancelOrderAction;
use App\Actions\Orders\RepeatOrderAction;
use App\Http\Requests\Orders\CancelOrderRequest;
use App\Models\Order;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Services\Orders\OrderLifecycleService;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use App\ViewModel\Orders\OrderDeliveryViewModel;
use App\ViewModel\Orders\OrderDetailsViewModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderLifecycleService $lifecycle,
    ) {}

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
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);

        $deliveryViewModels = $orders->getCollection()->mapWithKeys(
            static fn (Order $order): array => [$order->id => new OrderDeliveryViewModel($order)],
        );

        return view('orders.index', compact('breadcrumbs', 'orders', 'deliveryViewModels'));
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
            sectionTitle: __('Мои заказы'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $order->load('items', 'shipment');
        $viewModel = new OrderDetailsViewModel($order, $this->lifecycle);

        return view('orders.show', compact('order', 'breadcrumbs', 'viewModel'));
    }

    public function cancel(Request $request, Order $order, CancelOrderAction $cancelOrder): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->user_id === $user->id, 404);
        abort_unless($order->status->isNew() && $order->cancellation_state === null, 403);

        $reason = app(CancelOrderRequest::class)->validated('reason') ?? __('Отмена покупателем');
        $cancelOrder->run($order, $user, $reason, onlyIfNew: true);

        return redirect()->route('orders.show', $order)->with('order_message', __('Запрос отмены принят. Состояние возврата отображается в заказе.'));
    }

    public function repeat(Request $request, Order $order, RepeatOrderAction $repeatOrder): RedirectResponse
    {
        $user = $request->user();
        abort_unless($order->user_id === $user->id, 404);

        $repeatOrder->execute($user, $order);

        return redirect()->route('checkout');
    }
}
