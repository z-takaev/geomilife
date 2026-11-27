<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\DTO\Orders\CheckoutCalculationDto;
use App\DTO\Orders\CheckoutInputDto;
use App\DTO\Orders\CheckoutItemDto;
use App\DTO\Orders\PackedPackageDto;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Customers\SavedDeliveryLocationService;
use App\Support\Phone\PhoneFormat;
use App\Support\Values\Money;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly SavedDeliveryLocationService $locations,
        private readonly CheckoutDeliveryService $delivery,
        private readonly OrderNotificationService $notifications,
        private readonly CheckoutStateService $state,
    ) {}

    /**
     * Возвращает доступные способы получения заказа для города.
     */
    public function availableDeliveryMethods(?City $city): array
    {
        return $this->delivery->availableMethods($city);
    }

    /**
     * Рассчитывает состав, доставку и итоговую стоимость заказа по данным формы.
     */
    public function calculateOrder(CheckoutInputDto $input): CheckoutCalculationDto
    {
        $cartData = $this->calculateCart();

        $items = $cartData['items'];
        $subtotal = $cartData['subtotal'];
        $city = $input->cityId === null ? null : City::cachedById($input->cityId);
        $deliveryMethod = $input->deliveryMethod;
        $deliveryPrice = Money::fromMinor(0);
        $deliveryDetails = [];
        $address = [];
        $packages = [];
        $point = null;

        if ($city !== null) {
            $address = ['city_id' => $city->id, 'city' => $city->name, 'city_code' => $city->cdek_code];

            $delivery = $this->delivery->calculate($city, $deliveryMethod, $input, $items);
            $deliveryPrice = $delivery->price;
            $deliveryDetails = $delivery->details;
            $address += $delivery->address;
            $packages = $delivery->packages;
            $point = $delivery->point;
        }

        $total = $subtotal->add($deliveryPrice);

        $fingerprint = hash('sha256', json_encode([
            $this->fingerprintItems($items),
            $city?->id,
            $deliveryMethod?->value,
            $input->paymentMethod?->value ?? '',
            $deliveryDetails,
            $address,
            array_map(static fn (PackedPackageDto $package): array => $package->toArray(), $packages),
        ], JSON_THROW_ON_ERROR));

        return new CheckoutCalculationDto(
            items: $items,
            subtotal: $subtotal,
            delivery: $deliveryPrice,
            total: $total,
            city: $city,
            details: $deliveryDetails,
            address: $address,
            packages: $packages,
            point: $point,
            fingerprint: $fingerprint,
            isPreorder: $cartData['is_preorder'],
        );
    }

    /**
     * Возвращает пункты СДЭК, подходящие для города и упаковок заказа.
     */
    public function deliveryPoints(City $city, array $packages = []): array
    {
        return $this->delivery->deliveryPoints($city, $packages);
    }

    /**
     * Находит заказ текущего покупателя по ключу идемпотентности.
     */
    public function findPlacedOrder(string $idempotencyKey): ?Order
    {
        $query = Order::query()->where('idempotency_key', $idempotencyKey);

        if (Auth::check()) {
            $query->where('user_id', Auth::id());
        } else {
            $query->whereNull('user_id')->where('checkout_session_hash', $this->sessionHash());
        }

        return $query->first();
    }

    /**
     * Проверяет подтверждённый расчёт и оформляет заказ без повторного создания.
     */
    public function placeOrder(CheckoutInputDto $input, string $idempotencyKey, int $quotedTotalMinor): Order
    {
        if ($existing = $this->findPlacedOrder($idempotencyKey)) {
            return $existing;
        }

        $calculation = $this->calculateOrder($input);
        $saved = $this->state->getQuote($idempotencyKey);

        if (
            $saved === null
            || $saved->total->minorAmount() !== $quotedTotalMinor
            || ! $calculation->total->equals($saved->total)
            || ! hash_equals($saved->fingerprint, $calculation->fingerprint)
        ) {
            throw ValidationException::withMessages(['quoted_total_minor' => __('Условия заказа изменились. Проверьте итог и подтвердите его заново.')]);
        }

        $city = $calculation->city;
        $deliveryMethod = $input->deliveryMethod;

        if ($city === null || $deliveryMethod === null || ! array_key_exists($deliveryMethod->value, $this->availableDeliveryMethods($city))) {
            throw ValidationException::withMessages(['delivery_method' => __('Выбранный способ получения недоступен.')]);
        }

        if ($deliveryMethod->isCdek() && $calculation->point === null) {
            throw ValidationException::withMessages(['delivery_point_code' => __('Выберите пункт получения.')]);
        }

        $lockName = 'checkout:'.$idempotencyKey;
        $lock = DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName]);

        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw ValidationException::withMessages(['idempotency_key' => __('Не удалось заблокировать оформление заказа. Попробуйте ещё раз.')]);
        }

        try {
            return DB::transaction(function () use ($input, $idempotencyKey, $calculation, $deliveryMethod): Order {
                /** @var User */
                $user = Auth::user();

                if ($user !== null) {
                    User::query()
                        ->whereKey($user->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                if ($existing = $this->findPlacedOrder($idempotencyKey)) {
                    return $existing;
                }

                if (Order::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                    throw ValidationException::withMessages(['idempotency_key' => __('Оформление недоступно. Обновите страницу.')]);
                }

                $this->assertItemsUnchanged($calculation);

                $order = $this->createOrderRecord($user, $input, $calculation, $idempotencyKey);

                $this->createOrderItems($order, $calculation->items);

                $this->rememberDelivery($user, $deliveryMethod, $calculation);

                $this->cart->removeOrderedItems($this->itemQuantities($calculation->items));

                if (! $order->payment_method->isOnline()) {
                    $this->notifications->queue($order, 'CREATED', 'created:'.$order->id);
                }

                return $order;
            });
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    private function calculateCart(): array
    {
        $quantities = $this->cart->quantities();

        if ($quantities === []) {
            throw ValidationException::withMessages(['cart' => __('Корзина пуста.')]);
        }

        $variants = ProductVariant::query()
            ->with('product.media')
            ->whereKey(array_keys($quantities))
            ->available()
            ->get();

        if ($variants->count() !== count($quantities) || $variants->pluck('is_preorder')->unique()->count() > 1) {
            throw ValidationException::withMessages(['cart' => __('Состав корзины изменился. Проверьте доступность товаров.')]);
        }

        $items = [];
        $subtotal = Money::fromMinor(0);

        foreach ($variants as $variant) {
            $quantity = filter_var($quantities[$variant->id], FILTER_VALIDATE_INT);

            if ($quantity === false || $quantity < 1 || $quantity > 1000) {
                throw ValidationException::withMessages(['cart' => __('Недопустимое количество товара.')]);
            }

            $lineTotal = $variant->price->multiply($quantity);
            $subtotal = $subtotal->add($lineTotal);

            $items[] = new CheckoutItemDto(
                productId: $variant->product_id,
                productVariantId: $variant->id,
                productName: $variant->product->name,
                variantName: $variant->label(),
                sku: $variant->sku,
                quantity: $quantity,
                price: $variant->price,
                oldPrice: $variant->old_price,
                total: $lineTotal,
                weight: (int) $variant->weight,
                length: (int) $variant->length,
                width: (int) $variant->width,
                height: (int) $variant->height,
                image: $variant->product->getFirstMediaUrl('image', 'thumbnail'),
                url: route('products.show', [$variant->product, 'variant' => $variant->id]),
            );
        }

        return compact('items', 'subtotal') + ['is_preorder' => (bool) $variants->first()->is_preorder];
    }

    private function assertItemsUnchanged(CheckoutCalculationDto $calculation): void
    {
        $items = $calculation->items;

        Product::query()
            ->whereKey(array_map(static fn (CheckoutItemDto $item): int => $item->productId, $items))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $variants = ProductVariant::query()
            ->whereKey(array_map(static fn (CheckoutItemDto $item): int => $item->productVariantId, $items))
            ->available()
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $variant = $variants->get($item->productVariantId);

            $oldPriceUnchanged = ($variant?->old_price === null && $item->oldPrice === null)
                || ($variant?->old_price !== null && $item->oldPrice !== null && $variant->old_price->equals($item->oldPrice));

            $unchanged = $variant !== null && $variant->price->equals($item->price)
                && $oldPriceUnchanged
                && $variant->is_preorder === $calculation->isPreorder;

            foreach (['weight', 'length', 'width', 'height'] as $field) {
                $unchanged = $unchanged && (int) $variant?->{$field} === $item->{$field};
            }

            if (! $unchanged) {
                throw ValidationException::withMessages(['quoted_total_minor' => __('Состав или цена изменились. Проверьте итог и подтвердите заказ заново.')]);
            }
        }
    }

    private function sessionHash(): string
    {
        return hash_hmac('sha256', session()->getId(), (string) config('app.key'));
    }

    private function createOrderRecord(?User $user, CheckoutInputDto $input, CheckoutCalculationDto $calculation, string $idempotencyKey): Order
    {
        return Order::query()->create([
            'user_id' => $user?->id,
            'city_id' => $calculation->city->id,
            'status' => OrderStatusEnum::NEW,
            'payment_status' => PaymentStatusEnum::UNPAID,
            'delivery_method' => $input->deliveryMethod,
            'payment_method' => $input->paymentMethod,
            'subtotal' => $calculation->subtotal,
            'delivery_price' => $calculation->delivery,
            'total' => $calculation->total,
            'customer_name' => trim($input->firstName.' '.$input->lastName),
            'customer_phone' => PhoneFormat::normalize($input->phone),
            'customer_email' => $input->email,
            'delivery_address' => $calculation->address,
            'delivery_details' => $calculation->details,
            'packages' => array_map(static fn (PackedPackageDto $package): array => $package->toArray(), $calculation->packages),
            'comment' => $input->comment,
            'is_preorder' => $calculation->isPreorder,
            'idempotency_key' => $idempotencyKey,
            'checkout_session_hash' => $this->sessionHash(),
        ]);
    }

    private function createOrderItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item->productId,
                'product_variant_id' => $item->productVariantId,
                'product_name' => $item->productName,
                'variant_name' => $item->variantName,
                'sku' => $item->sku,
                'price' => $item->price,
                'old_price' => $item->oldPrice,
                'quantity' => $item->quantity,
                'total' => $item->total,
            ]);
        }
    }

    private function rememberDelivery(?User $user, DeliveryMethodEnum $deliveryMethod, CheckoutCalculationDto $calculation): void
    {
        if ($user !== null && $deliveryMethod->isCourier()) {
            $this->locations->rememberAddress($user, $calculation->address);
        } elseif ($user !== null && $deliveryMethod->isCdek()) {
            $this->locations->rememberDeliveryPoint($user, $calculation->city, $calculation->point);
        }
    }

    private function fingerprintItems(array $items): array
    {
        return array_map(static fn (CheckoutItemDto $item): array => $item->fingerprintData(), $items);
    }

    /**
     * @param  list<CheckoutItemDto>  $items
     * @return array<int, int>
     */
    private function itemQuantities(array $items): array
    {
        $quantities = [];

        foreach ($items as $item) {
            $quantities[$item->productVariantId] = $item->quantity;
        }

        return $quantities;
    }
}
