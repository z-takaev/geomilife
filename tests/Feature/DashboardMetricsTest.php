<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\SalesOverview;
use App\Filament\Widgets\TopProducts;
use App\Models\Order;
use App\Services\Dashboard\DashboardMetricsService;
use App\Support\Values\Money;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

function dashboardOrder(array $attributes = []): Order
{
    return Order::unguarded(fn (): Order => fulfillmentOrder($attributes));
}

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 11, 11)->setTime(12, 0));
    Filament::setCurrentPanel('admin');
    $this->actingAs(fulfillmentEmployee(FilamentUserRoleEnum::ADMIN), 'filament');
});

it('выбирает сегодня по умолчанию и убирает выбор количества товаров', function (): void {
    Livewire::test(Dashboard::class)
        ->assertSet('filters.period', 'day')
        ->assertSee('За всё время')
        ->assertDontSee('Топ-20');
});

it('считает заказы отдельно от продаж и включает доставку в выручку и средний чек', function (): void {
    dashboardOrder(['total' => Money::fromRubles(1550)]);
    dashboardOrder(['total' => Money::fromRubles(2450)]);
    foreach (PaymentStatusEnum::cases() as $status) {
        if ($status !== PaymentStatusEnum::PAID) {
            dashboardOrder(['payment_status' => $status]);
        }
    }
    dashboardOrder(['status' => 'CANCELLED']);

    $metrics = app(DashboardMetricsService::class)->overview([]);

    expect($metrics['orders'])->toBe(10)
        ->and($metrics['revenue'])->toBe(400000)
        ->and($metrics['average'])->toBe(200000)
        ->and($metrics['repeatPercentage'])->toBe(0.0);

    Livewire::test(SalesOverview::class)->assertSee('4 000 ₽')->assertSee('2 000 ₽');
});

it('применяет границы часового пояса приложения и дату создания к выбранному периоду', function (array $filters, string $start, string $end): void {
    dashboardOrder(['created_at' => $start]);
    dashboardOrder(['created_at' => $end]);
    dashboardOrder(['created_at' => CarbonImmutable::parse($start)->subSecond()]);
    dashboardOrder(['created_at' => CarbonImmutable::parse($end)->addSecond()]);

    $metrics = app(DashboardMetricsService::class)->overview($filters);
    expect($metrics['orders'])->toBe(2)->and($metrics['revenue'])->toBe(310000);
})->with([
    'сегодня' => [['period' => 'day'], '2026-11-11 00:00:00', '2026-11-11 23:59:59'],
    'неделя' => [['period' => 'week'], '2026-11-05 00:00:00', '2026-11-11 23:59:59'],
    'месяц' => [['period' => 'month'], '2026-11-01 00:00:00', '2026-11-11 23:59:59'],
    'произвольный' => [['period' => 'custom', 'startDate' => '2026-11-01', 'endDate' => '2026-11-03'], '2026-11-01 00:00:00', '2026-11-03 23:59:59'],
]);

it('учитывает историю до периода и различает заказы с одинаковой датой для повторных покупок', function (): void {
    $customer = CheckoutFixtures::user();
    dashboardOrder(['user_id' => $customer->id, 'created_at' => '2026-10-01']);
    dashboardOrder(['user_id' => $customer->id]);
    $newCustomer = CheckoutFixtures::user();
    dashboardOrder(['user_id' => $newCustomer->id]);
    dashboardOrder(['user_id' => $newCustomer->id]);
    dashboardOrder(['user_id' => $newCustomer->id, 'payment_status' => 'PENDING']);

    expect(app(DashboardMetricsService::class)->overview(['period' => 'day'])['repeatPercentage'])->toBe(66.7);
    expect(app(DashboardMetricsService::class)->overview([])['repeatPercentage'])->toBe(50.0);
});

it('не считает неоплаченные отмененные и возвращенные покупки историей клиента', function (): void {
    $customer = CheckoutFixtures::user();
    foreach ([['status' => 'CANCELLED'], ['payment_status' => 'REFUNDED'], ['payment_status' => 'AUTHORIZED']] as $attributes) {
        dashboardOrder([...$attributes, 'user_id' => $customer->id, 'created_at' => '2026-10-01']);
    }
    dashboardOrder(['user_id' => $customer->id]);
    expect(app(DashboardMetricsService::class)->overview(['period' => 'day'])['repeatPercentage'])->toBe(0.0);
});

it('возвращает нули и пустой топ при отсутствии заказов', function (): void {
    expect(app(DashboardMetricsService::class)->overview([]))->toBe([
        'orders' => 0, 'revenue' => 0, 'average' => 0, 'repeatPercentage' => 0.0,
    ])->and(app(DashboardMetricsService::class)->topProducts([]))->toBe([]);
    Livewire::test(SalesOverview::class)->assertSee('0 ₽')->assertSee('0 %');
    Livewire::test(TopProducts::class)->assertDontSee('Демонстрационный товар');
});

it('отклоняет некорректные даты для всех метрик и топа', function (array $filters): void {
    dashboardOrder();
    expect(app(DashboardMetricsService::class)->overview($filters))->toBeNull()
        ->and(app(DashboardMetricsService::class)->topProducts($filters))->toBe([]);
    Livewire::test(SalesOverview::class, ['pageFilters' => $filters])->assertSee('—');
})->with([
    [['period' => 'custom']],
    [['period' => 'custom', 'startDate' => '2026-11-10', 'endDate' => '2026-11-01']],
    [['period' => 'custom', 'startDate' => '2026-11-11', 'endDate' => '2026-11-12']],
    [['period' => 'custom', 'startDate' => '2026-02-30', 'endDate' => '2026-11-11']],
]);

it('объединяет варианты одного товара и применяет период и оплату к топу', function (): void {
    $variant = CheckoutFixtures::variant();
    $order = dashboardOrder();
    foreach ([2, 3] as $quantity) {
        fulfillmentOrderItem($order, ['product_id' => $variant->product_id, 'product_variant_id' => $variant->id, 'quantity' => $quantity]);
    }
    foreach ([['created_at' => '2026-10-01'], ['payment_status' => 'PENDING'], ['status' => 'CANCELLED'], ['payment_status' => 'REFUNDED']] as $attributes) {
        fulfillmentOrderItem(dashboardOrder($attributes), ['product_id' => $variant->product_id, 'product_variant_id' => $variant->id, 'quantity' => 20]);
    }
    $products = app(DashboardMetricsService::class)->topProducts(['period' => 'day']);
    expect($products)->toHaveCount(1)
        ->and($products[0]['quantity'])->toBe(5)
        ->and($products[0]['name'])->toBe($variant->product->name);
    expect(app(DashboardMetricsService::class)->topProducts([])[0]['quantity'])->toBe(25);
    Livewire::test(TopProducts::class, ['pageFilters' => ['period' => 'day']])->assertSee($variant->product->name);
});

it('показывает ровно десять лидеров по количеству', function (): void {
    $order = dashboardOrder();
    foreach (range(1, 12) as $quantity) {
        $variant = CheckoutFixtures::variant();
        fulfillmentOrderItem($order, ['product_id' => $variant->product_id, 'product_variant_id' => $variant->id, 'quantity' => $quantity]);
    }
    $products = app(DashboardMetricsService::class)->topProducts([]);
    expect($products)->toHaveCount(10)
        ->and(array_column($products, 'quantity'))->toBe(range(12, 3))
        ->and(array_column($products, 'position'))->toBe(range(1, 10));
});
