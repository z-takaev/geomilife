<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class DashboardMetricsService
{
    public function overview(array $filters): ?array
    {
        $orders = $this->orders($filters);

        if ($orders === null) {
            return null;
        }

        $orderCount = (clone $orders)->count();

        $paidOrders = $orders->where('payment_status', PaymentStatusEnum::PAID);

        $sales = (clone $paidOrders)->toBase()
            ->selectRaw('COUNT(*) AS count, COALESCE(SUM(total), 0) AS revenue')
            ->first();

        $paidCount = (int) $sales->count;

        $revenue = (int) $sales->revenue;

        $repeatOrderCount = $paidOrders->whereNotNull('user_id')
            ->whereExists(function (QueryBuilder $query): void {
                $query->selectRaw('1')->from('orders as previous')
                    ->whereColumn('previous.user_id', 'orders.user_id')
                    ->where('previous.status', '!=', OrderStatusEnum::CANCELLED)
                    ->where('previous.payment_status', PaymentStatusEnum::PAID)
                    ->where(function (QueryBuilder $query): void {
                        $query->whereColumn('previous.created_at', '<', 'orders.created_at')
                            ->orWhere(function (QueryBuilder $query): void {
                                $query->whereColumn('previous.created_at', 'orders.created_at')
                                    ->whereColumn('previous.id', '<', 'orders.id');
                            });
                    });
            })->count();

        return [
            'orders' => $orderCount,
            'revenue' => $revenue,
            'average' => $paidCount === 0 ? 0 : (int) round($revenue / $paidCount),
            'repeatPercentage' => $paidCount === 0 ? 0.0 : round($repeatOrderCount / $paidCount * 100, 1),
        ];
    }

    public function topProducts(array $filters): array
    {
        $orders = $this->orders($filters);

        if ($orders === null) {
            return [];
        }

        return DB::table('order_items')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('order_items.order_id', $orders->where('payment_status', PaymentStatusEnum::PAID)->select('orders.id'))
            ->select('order_items.product_id')
            ->selectRaw('COALESCE(MAX(products.name), MAX(order_items.product_name)) AS name, SUM(order_items.quantity) AS quantity')
            ->groupBy('order_items.product_id')
            ->orderByDesc('quantity')
            ->orderBy('order_items.product_id')
            ->limit(10)
            ->get()
            ->map(fn (object $product, int $index): array => [
                'position' => $index + 1,
                'name' => $product->name,
                'quantity' => (int) $product->quantity,
            ])->all();
    }

    private function orders(array $filters): ?Builder
    {
        $orders = Order::query()->where('status', '!=', OrderStatusEnum::CANCELLED);
        $today = CarbonImmutable::today();
        $end = $today->addDay();

        switch ($filters['period'] ?? 'all') {
            case 'day':
                $start = $today;
                break;
            case 'week':
                $start = $today->subDays(6);
                break;
            case 'month':
                $start = $today->startOfMonth();
                break;
            case 'custom':
                if (Validator::make($filters, [
                    'startDate' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$today->toDateString()],
                    'endDate' => ['required', 'date_format:Y-m-d', 'after_or_equal:startDate', 'before_or_equal:'.$today->toDateString()],
                ])->fails()) {
                    return null;
                }

                $start = CarbonImmutable::parse($filters['startDate']);
                $end = CarbonImmutable::parse($filters['endDate'])->addDay();
                break;
            default:
                return $orders;
        }

        return $orders->where('created_at', '>=', $start)
            ->where('created_at', '<', $end);
    }
}
