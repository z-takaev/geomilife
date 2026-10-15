<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class OrderController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function index(): View
    {
        $seo = $this->seoService->forRoute(
            routeName: 'orders.index',
            fallbackTitle: __('Мои заказы'),
        );

        return view('orders.index', compact('seo'));
    }

    public function show(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 404);

        $seo = $this->seoService->forRoute(
            routeName: 'orders.show',
            fallbackTitle: __('Заказ №:number', ['number' => $order->number]),
        );

        return view('orders.show', compact('order', 'seo'));
    }
}
