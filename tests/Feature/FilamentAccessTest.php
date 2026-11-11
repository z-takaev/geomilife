<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\SalesOverview;
use App\Filament\Widgets\TopProducts;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

beforeEach(function (): void {
    Http::preventStrayRequests();
    Filament::setCurrentPanel('admin');
    $this->actingAs(fulfillmentEmployee(), 'filament');
});

it('оставляет менеджеру только заказы товары и покупателей', function (): void {
    $allowed = [OrderResource::class, ProductResource::class, UserResource::class];
    foreach (Filament::getResources() as $resource) {
        expect($resource::canViewAny())->toBe(in_array($resource, $allowed, true));
        if (! in_array($resource, $allowed, true)) {
            $this->get($resource::getUrl())->assertForbidden();
        }
    }
    $urls = collect(Filament::getNavigation())->flatMap(fn ($group) => $group->getItems())
        ->map(fn ($item) => $item->getUrl())->all();
    expect($urls)->toEqualCanonicalizing(array_map(fn ($resource) => $resource::getUrl(), $allowed));
    $this->get(ManageSettings::getUrl())->assertForbidden();
    $this->get(Dashboard::getUrl())->assertForbidden();
    $this->get('/admin')->assertRedirect(OrderResource::getUrl());
});

it('разрешает менеджеру только просмотр товаров и покупателей', function (): void {
    $variant = CheckoutFixtures::variant();
    $customer = CheckoutFixtures::user();
    foreach ([[ProductResource::class, $variant->product], [UserResource::class, $customer]] as [$resource, $record]) {
        $this->get($resource::getUrl())->assertOk();
        $this->get($resource::getUrl('view', ['record' => $record]))->assertOk();
        $this->get($resource::getUrl('edit', ['record' => $record]))->assertForbidden();
        $this->get($resource::getUrl('create'))->assertForbidden();
        expect($resource::canDelete($record))->toBeFalse();
    }
    Livewire::test(ListProducts::class)->assertActionHidden('importExcel')->assertActionHidden('exportExcel')
        ->call('updateTableColumnState', 'is_active', (string) $variant->product_id, false);
    expect($variant->product->fresh()->is_active)->toBeTrue();
    Livewire::test(ListUsers::class)->assertTableActionHidden('exportExcel')
        ->call('updateTableColumnState', 'is_blocked', (string) $customer->id, true);
    expect($customer->fresh()->is_blocked)->toBeFalse();
});

it('не дает менеджеру читать статистику через виджеты', function (): void {
    expect(SalesOverview::canView())->toBeFalse()->and(TopProducts::canView())->toBeFalse();
    Livewire::test(SalesOverview::class)->assertForbidden();
    Livewire::test(TopProducts::class)->assertForbidden();
});

it('открывает администратору все разделы', function (): void {
    $this->actingAs(fulfillmentEmployee(FilamentUserRoleEnum::ADMIN), 'filament');
    foreach (Filament::getResources() as $resource) {
        expect($resource::canViewAny())->toBeTrue();
        $this->get($resource::getUrl())->assertOk();
    }
    expect(Dashboard::canAccess())->toBeTrue()->and(ManageSettings::canAccess())->toBeTrue();
});

it('разрешает менеджеру сверку онлайн оплаты и отмену', function (): void {
    $order = fulfillmentOrder();
    Livewire::test(ViewOrder::class, ['record' => $order->id])->assertActionVisible('checkPayment')
        ->mountAction('checkPayment')->assertActionMounted('checkPayment')->callMountedAction()->assertNotified();
    Http::assertNothingSent();
    Livewire::test(ViewOrder::class, ['record' => $order->id])->assertActionVisible('cancel');
});
