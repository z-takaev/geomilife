<?php

declare(strict_types=1);

use App\Services\Orders\OrderLifecycleService;
use App\Support\Values\Money;
use App\ViewModel\Orders\OrderConfirmationViewModel;
use App\ViewModel\Orders\OrderDeliveryViewModel;
use App\ViewModel\Orders\OrderDetailsViewModel;
use App\ViewModel\Orders\OrderManagementViewModel;
use App\ViewModel\Orders\PackingSlipViewModel;
use App\ViewModel\Products\ProductVariantViewModel;
use App\ViewModel\Products\ProductViewModel;
use Tests\Support\CheckoutFixtures;

require_once __DIR__.'/../../Support/FulfillmentFixtures.php';

it('представляет сведения о доставке типизированным объектом', function (): void {
    $order = CheckoutFixtures::order(CheckoutFixtures::user(), [
        'delivery_method' => 'COURIER',
        'delivery_address' => [
            'city' => 'Владикавказ',
            'street' => 'Мира',
            'house' => '15',
            'building' => '2',
            'working_hours' => '09:00–18:00',
        ],
    ]);

    $viewModel = new OrderDeliveryViewModel($order);

    expect($viewModel->label)->toBe('Курьер')
        ->and($viewModel->address)->toBe('Владикавказ, Мира, дом 15, корпус 2')
        ->and($viewModel->workingHours)->toBe('09:00–18:00');
});

it('создаёт отдельные представления списка деталей и подтверждения заказа', function (): void {
    $order = CheckoutFixtures::order(CheckoutFixtures::user(), [
        'delivery_price' => Money::fromRubles(350),
        'is_preorder' => true,
    ]);
    $lifecycle = app(OrderLifecycleService::class);

    $delivery = new OrderDeliveryViewModel($order);
    $details = new OrderDetailsViewModel($order, $lifecycle);
    $confirmation = new OrderConfirmationViewModel($order);

    expect($delivery->priceLabel)->toBe('350 ₽')
        ->and($details->delivery)->toBeInstanceOf(OrderDeliveryViewModel::class)
        ->and($details->stages)->toBeArray()
        ->and($details->canCancel)->toBeTrue()
        ->and($confirmation->createdAtLabel)->not->toBeEmpty()
        ->and($confirmation->deliveryHint)->toBe('Точные сроки доставки согласовываются отдельно с менеджером');
});

it('предоставляет административное представление через контейнер', function (): void {
    $order = fulfillmentOrder();
    $viewModel = app(OrderManagementViewModel::class);

    expect($viewModel->processingStages($order))->toBeArray()->not->toBeEmpty()
        ->and($viewModel->processingHint($order))->toBe('Проверьте заказ и выполните следующий шаг.')
        ->and($viewModel->accountLabel($order))->toBe('Гость');
});

it('типизирует данные товарных вариантов', function (): void {
    $variant = CheckoutFixtures::variant([
        'price' => Money::fromRubles(750),
        'old_price' => Money::fromRubles(900),
        'is_preorder' => true,
    ]);
    $product = $variant->product()->with('activeVariants')->firstOrFail();

    $viewModel = new ProductViewModel($product, $variant->id);

    expect($viewModel->selectedVariant)->toBeInstanceOf(ProductVariantViewModel::class)
        ->and($viewModel->selectedVariant->id)->toBe($variant->id)
        ->and($viewModel->selectedVariant->price)->toBe('750 ₽')
        ->and($viewModel->selectedVariant->oldPrice)->toBe('900 ₽')
        ->and($viewModel->selectedVariant->isPreorder)->toBeTrue();
});

it('предоставляет упаковочный лист готовыми свойствами', function (): void {
    $order = fulfillmentOrder();
    fulfillmentOrderItem($order);
    $order->load('items', 'shipment');

    $viewModel = new PackingSlipViewModel($order);

    expect($viewModel->order)->toBe($order)
        ->and($viewModel->packages)->toHaveCount(1)
        ->and($viewModel->packages[0]['contents'][0])
        ->toMatchArray(['label' => 'Масло тмина 250 мл', 'sku' => 'FULFILLMENT-250', 'quantity' => 1]);
});
