<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Orders\OrderPaymentService;
use App\Services\Tbank\Exceptions\TbankApiException;
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

    public function guestShow(Request $request, string $token): View
    {
        $order = $this->guestOrder($token);
        $breadcrumbs = Breadcrumbs::make(BreadcrumbItem::make(__('Детали заказа'), $request->fullUrl()));
        seo()->forRoute(routeName: 'orders.guest.show', fallbackTitle: __('Заказ №:number', ['number' => $order->number]))
            ->schema(BreadcrumbSchema::make($breadcrumbs));
        $presentation = OrderViewModel::data($order, true);

        return view('orders.show', compact('order', 'breadcrumbs', 'presentation'));
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        $this->cancelOrder($request, $order, $request->user());

        return redirect()->route('orders.show', $order)->with('order_message', __('Запрос отмены принят. Состояние возврата отображается в заказе.'));
    }

    public function guestCancel(Request $request, string $token): RedirectResponse
    {
        $order = $this->guestOrder($token);
        $this->cancelOrder($request, $order, null);

        return redirect(OrderViewModel::url($order))->with('order_message', __('Запрос отмены принят. Состояние возврата отображается в заказе.'));
    }

    public function pay(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return $this->startPayment($order);
    }

    public function guestPay(Request $request, string $token): RedirectResponse
    {
        return $this->startPayment($this->guestOrder($token));
    }

    private function guestOrder(string $token): Order
    {
        return Order::query()->where('guest_token', $token)->firstOrFail();
    }

    private function cancelOrder(Request $request, Order $order, ?User $actor): void
    {
        abort_unless($order->status->isNew() && $order->cancellation_state === null, 403);
        $values = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        app(OrderLifecycleService::class)->cancel($order, $actor, $values['reason'] ?? __('Отмена покупателем'), onlyIfNew: true);
    }

    private function startPayment(Order $order): RedirectResponse
    {
        try {
            $payment = app(OrderPaymentService::class)->start($order);

            if (blank($payment->payment_url)) {
                return redirect(OrderViewModel::url($order))->with('order_message', __('Проверяем состояние платежа. Повторите попытку позже; заказ сохранён.'));
            }

            return redirect()->away($payment->payment_url);
        } catch (TbankApiException $exception) {
            report($exception);

            return redirect(OrderViewModel::url($order))->with('order_message', __('Не удалось открыть оплату. Заказ сохранён, повторите попытку позже.'));
        }
    }
}
