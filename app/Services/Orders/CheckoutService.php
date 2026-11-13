<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\DTO\Cdek\CdekCalculateTariffRequestDto;
use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\DTO\Cdek\CdekGetDeliveryPointsRequestDto;
use App\DTO\Cdek\CdekLocationDto;
use App\DTO\Cdek\CdekPackageDto;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Cdek\CdekService;
use App\Services\Customers\SavedDeliveryLocationService;
use App\Support\Phone\PhoneFormat;
use App\Support\Values\Money;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CheckoutService
{
    public function __construct(
        private readonly SavedDeliveryLocationService $locations,
        private readonly OrderPackingService $packing,
        private readonly OrderNotificationService $notifications,
    ) {}

    /**
     * Возвращает доступные способы получения заказа для города.
     */
    public function availableDeliveryMethods(?City $city): array
    {
        if ($city === null || ! $city->is_active) {
            return [];
        }

        $methods = [];

        if ($city->is_pickup_available) {
            $methods[DeliveryMethodEnum::PICKUP->value] = DeliveryMethodEnum::PICKUP->getLabel();
        }

        if ($city->is_courier_available) {
            $methods[DeliveryMethodEnum::COURIER->value] = DeliveryMethodEnum::COURIER->getLabel();
        }

        if (! $city->is_cdek_excluded && $city->cdek_code > 0) {
            $methods[DeliveryMethodEnum::CDEK->value] = DeliveryMethodEnum::CDEK->getLabel();
        }

        return $methods;
    }

    /**
     * Рассчитывает состав, доставку и итоговую стоимость заказа по данным формы.
     */
    public function calculateOrder(array $form): array
    {
        $cartData = $this->calculateCart();
        $items = $cartData['items'];
        $subtotal = $cartData['subtotal'];
        $city = City::query()
            ->whereKey($form['city_id'] ?? null)
            ->where('is_active', true)
            ->first();
        $deliveryMethod = $form['delivery_method'] ?? '';
        $deliveryPrice = Money::fromMinor(0);
        $deliveryDetails = [];
        $address = [];
        $packages = [];
        $point = null;

        if ($city !== null) {
            $address = ['city_id' => $city->id, 'city' => $city->name, 'city_code' => $city->cdek_code];

            if ($deliveryMethod === DeliveryMethodEnum::PICKUP->value) {
                $address += [
                    'address' => $city->pickup_address,
                    'working_hours' => $city->pickup_working_hours,
                    'phone' => $city->pickup_phone,
                ];
            } elseif ($deliveryMethod === DeliveryMethodEnum::COURIER->value) {
                ['delivery' => $deliveryPrice, 'details' => $deliveryDetails, 'address' => $deliveryAddress] = $this->calculateCourier($city, $form);
                $address += $deliveryAddress;
            } elseif ($deliveryMethod === DeliveryMethodEnum::CDEK->value && array_key_exists(DeliveryMethodEnum::CDEK->value, $this->availableDeliveryMethods($city))) {
                [
                    'delivery' => $deliveryPrice,
                    'details' => $deliveryDetails,
                    'address' => $deliveryAddress,
                    'packages' => $packages,
                    'point' => $point
                ] = $this->calculateCdek($city, $form, $items);
                $address += $deliveryAddress;
            }
        }

        $total = $subtotal->add($deliveryPrice);
        $fingerprint = hash('sha256', json_encode([$items, $city?->id, $deliveryMethod, $form['payment_method'] ?? '', $deliveryDetails, $address, $packages], JSON_THROW_ON_ERROR));

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'delivery' => $deliveryPrice,
            'total' => $total,
            'city' => $city,
            'details' => $deliveryDetails,
            'address' => $address,
            'packages' => $packages,
            'point' => $point,
            'fingerprint' => $fingerprint,
            'is_preorder' => $cartData['is_preorder'],
        ];
    }

    /**
     * Возвращает пункты СДЭК, подходящие для города и упаковок заказа.
     */
    public function deliveryPoints(City $city, array $packages = []): array
    {
        $cacheKey = 'checkout:cdek:points:'.$city->cdek_code.':PVZ';
        $points = Cache::get($cacheKey);

        if (! is_array($points) || array_any($points, static fn (mixed $point): bool => ! $point instanceof CdekDeliveryPointResponseDto)) {
            $points = $this->loadDeliveryPoints($city);
            Cache::put($cacheKey, $points, now()->addMinutes(5));
        }

        return array_values(array_filter($points, fn (CdekDeliveryPointResponseDto $point): bool => $this->pointFitsPackages($point, $city, $packages)));
    }

    /**
     * Находит заказ текущего покупателя по ключу идемпотентности.
     */
    public function findPlacedOrder(string $idempotencyKey): ?Order
    {
        $query = Order::query()->where('idempotency_key', $idempotencyKey);

        if (auth()->check()) {
            $query->where('user_id', auth()->id());
        } else {
            $query->whereNull('user_id')->where('checkout_session_hash', $this->sessionHash());
        }

        return $query->first();
    }

    /**
     * Проверяет подтверждённый расчёт и оформляет заказ без повторного создания.
     */
    public function placeOrder(array $form, string $idempotencyKey, int $quotedTotalMinor): Order
    {
        if ($existing = $this->findPlacedOrder($idempotencyKey)) {
            return $existing;
        }

        $calculation = $this->calculateOrder($form);
        $saved = session('checkout.quotes.'.$idempotencyKey);

        if (! is_array($saved) || $quotedTotalMinor !== ($saved['total_minor'] ?? null)
            || $calculation['total']->minorAmount() !== $quotedTotalMinor || ! hash_equals($saved['fingerprint'] ?? '', $calculation['fingerprint'])) {
            throw ValidationException::withMessages(['quoted_total_minor' => __('Условия заказа изменились. Проверьте итог и подтвердите его заново.')]);
        }

        $city = $calculation['city'];
        $deliveryMethod = $form['delivery_method'];

        if ($city === null || ! array_key_exists($deliveryMethod, $this->availableDeliveryMethods($city))) {
            throw ValidationException::withMessages(['delivery_method' => __('Выбранный способ получения недоступен.')]);
        }

        if ($deliveryMethod === DeliveryMethodEnum::CDEK->value && $calculation['point'] === null) {
            throw ValidationException::withMessages(['delivery_point_code' => __('Выберите пункт получения.')]);
        }

        $lockName = 'checkout:'.$idempotencyKey;
        $lock = DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName]);

        if ((int) ($lock->acquired ?? 0) !== 1) {
            throw ValidationException::withMessages(['idempotency_key' => __('Не удалось заблокировать оформление заказа. Попробуйте ещё раз.')]);
        }

        try {
            return DB::transaction(function () use ($form, $idempotencyKey, $calculation, $deliveryMethod): Order {
                $user = auth()->user();

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

                $this->assertCurrentItems($calculation);
                $order = $this->createOrderRecord($user, $form, $calculation, $idempotencyKey);
                $this->saveItems($order, $calculation['items']);
                $this->rememberDelivery($user, $deliveryMethod, $calculation);

                app(CartService::class)->removeOrderedItems($calculation['items']);
                $this->notifications->queue($order, 'CREATED', 'created:'.$order->id);

                return $order;
            });
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    /**
     * Очищает номер телефона от лишних символов и заменяет начальную 8 на 7 для российских номеров.
     */
    public function normalizePhone(string $value): string
    {
        return PhoneFormat::normalize($value);
    }

    private function calculateCart(): array
    {
        $quantities = app(CartService::class)->quantities();

        if ($quantities === []) {
            throw ValidationException::withMessages(['cart' => __('Корзина пуста.')]);
        }

        $variants = ProductVariant::query()
            ->with('product.media')
            ->whereKey(array_keys($quantities))
            ->available()->get();

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
            $items[] = [
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->label(),
                'sku' => $variant->sku,
                'quantity' => $quantity,
                'price_minor' => $variant->price->minorAmount(),
                'old_price_minor' => $variant->old_price?->minorAmount(),
                'total_minor' => $lineTotal->minorAmount(),
                'weight' => (int) $variant->weight,
                'length' => (int) $variant->length,
                'width' => (int) $variant->width,
                'height' => (int) $variant->height,
                'image' => $variant->product->getFirstMediaUrl('image', 'thumbnail'),
                'url' => route('products.show', [$variant->product, 'variant' => $variant->id]),
            ];
        }

        return compact('items', 'subtotal') + ['is_preorder' => (bool) $variants->first()->is_preorder];
    }

    private function calculateCourier(City $city, array $form): array
    {
        $address = [];
        $basePrice = $city->courier_delivery_price ?? Money::fromMinor(0);
        $doorPrice = ($form['delivery_to_door'] ?? false) ? ($city->door_delivery_price ?? Money::fromMinor(0)) : Money::fromMinor(0);
        $floor = ($form['delivery_to_door'] ?? false) ? max(0, (int) ($form['floor'] ?? 0)) : 0;
        $liftingPrice = ($city->floor_lifting_price ?? Money::fromMinor(0))->multiply(max(0, $floor - 1));
        $deliveryPrice = $basePrice->add($doorPrice)->add($liftingPrice);
        $deliveryDetails = [
            'base_minor' => $basePrice->minorAmount(),
            'door_minor' => $doorPrice->minorAmount(),
            'lifting_minor' => $liftingPrice->minorAmount(),
            'delivery_to_door' => (bool) ($form['delivery_to_door'] ?? false),
        ];

        foreach (['street', 'house', 'building', 'entrance', 'floor', 'apartment', 'intercom'] as $field) {
            $address[$field] = trim((string) ($form[$field] ?? ''));
        }

        $address['address'] = trim($address['street'].' '.$address['house']);

        return [
            'delivery' => $deliveryPrice,
            'details' => $deliveryDetails,
            'address' => $address,
        ];
    }

    private function calculateCdek(City $city, array $form, array $items): array
    {
        $deliveryPrice = Money::fromMinor(0);
        $deliveryDetails = [];
        $address = [];
        $point = null;
        $packages = $this->packing->pack($items);
        $code = (string) ($form['delivery_point_code'] ?? '');

        if ($code !== '') {
            $point = collect($this->deliveryPoints($city, $packages))->first(static fn (CdekDeliveryPointResponseDto $value): bool => $value->code === $code);

            if ($point === null) {
                throw ValidationException::withMessages(['delivery_point_code' => __('Выбранный пункт недоступен для этого заказа.')]);
            }

            $calculation = app(CdekService::class)->calculateTariff(new CdekCalculateTariffRequestDto(
                fromLocation: new CdekLocationDto(code: (int) config('orders.cdek.city_code')),
                toLocation: new CdekLocationDto(code: $city->cdek_code),
                packages: array_map(static fn (array $package): CdekPackageDto => new CdekPackageDto(
                    weight: $package['weight'],
                    length: (int) ceil($package['length'] / 10),
                    width: (int) ceil($package['width'] / 10),
                    height: (int) ceil($package['height'] / 10),
                ), $packages),
                shipmentPoint: (string) config('orders.cdek.shipment_point'),
                deliveryPoint: $point->code,
            ), (int) config('orders.cdek.tariff_code'));

            $rawDeliveryPrice = $calculation->totalSum;
            $roundedDeliveryMinor = intdiv($rawDeliveryPrice->minorAmount() + 50, 100) * 100;
            $deliveryPrice = Money::fromMinor($roundedDeliveryMinor);
            $deliveryDetails = [
                'tariff_code' => (int) config('orders.cdek.tariff_code'),
                'original_delivery_minor' => $rawDeliveryPrice->minorAmount(),
                'period_min' => $calculation->periodMin,
                'period_max' => $calculation->periodMax,
            ];
            $address += ['code' => $point->code, 'type' => $point->type, 'address' => $point->address, 'work_time' => $point->workTime];
        }

        return [
            'delivery' => $deliveryPrice,
            'details' => $deliveryDetails,
            'address' => $address,
            'packages' => $packages,
            'point' => $point,
        ];
    }

    private function assertCurrentItems(array $calculation): void
    {
        $items = $calculation['items'];
        Product::query()
            ->whereKey(array_column($items, 'product_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $variants = ProductVariant::query()->whereKey(array_column($items, 'product_variant_id'))
            ->available()
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            $variant = $variants->get($item['product_variant_id']);
            $unchanged = $variant !== null && $variant->price->minorAmount() === $item['price_minor']
                && $variant->old_price?->minorAmount() === $item['old_price_minor']
                && $variant->is_preorder === $calculation['is_preorder'];

            foreach (['weight', 'length', 'width', 'height'] as $field) {
                $unchanged = $unchanged && (int) $variant?->{$field} === $item[$field];
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

    private function createOrderRecord(?User $user, array $form, array $calculation, string $idempotencyKey): Order
    {
        return Order::query()->create([
            'user_id' => $user?->id,
            'city_id' => $calculation['city']->id,
            'status' => OrderStatusEnum::NEW,
            'payment_status' => PaymentStatusEnum::UNPAID,
            'delivery_method' => $form['delivery_method'],
            'payment_method' => $form['payment_method'],
            'subtotal' => $calculation['subtotal'],
            'delivery_price' => $calculation['delivery'],
            'total' => $calculation['total'],
            'customer_name' => trim($form['first_name'].' '.$form['last_name']),
            'customer_phone' => $this->normalizePhone($form['phone']),
            'customer_email' => $form['email'],
            'delivery_address' => $calculation['address'],
            'delivery_details' => $calculation['details'],
            'packages' => $calculation['packages'],
            'comment' => $form['comment'] ?? null,
            'is_preorder' => $calculation['is_preorder'],
            'idempotency_key' => $idempotencyKey,
            'checkout_session_hash' => $this->sessionHash(),
        ]);
    }

    private function saveItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'],
                'product_name' => $item['product_name'],
                'variant_name' => $item['variant_name'],
                'sku' => $item['sku'],
                'price' => Money::fromMinor($item['price_minor']),
                'old_price' => $item['old_price_minor'] === null ? null : Money::fromMinor($item['old_price_minor']),
                'quantity' => $item['quantity'],
                'total' => Money::fromMinor($item['total_minor']),
            ]);
        }
    }

    private function rememberDelivery(?User $user, string $deliveryMethod, array $calculation): void
    {
        if ($user !== null && $deliveryMethod === DeliveryMethodEnum::COURIER->value) {
            $this->locations->rememberAddress($user, $calculation['address']);
        } elseif ($user !== null && $deliveryMethod === DeliveryMethodEnum::CDEK->value) {
            $this->locations->rememberDeliveryPoint($user, $calculation['city'], $calculation['point']);
        }
    }

    private function loadDeliveryPoints(City $city): array
    {
        $result = [];

        for ($page = 0; $page < 100; $page++) {
            $batch = app(CdekService::class)->getDeliveryPoints(new CdekGetDeliveryPointsRequestDto(cityCode: $city->cdek_code, size: 500, page: $page));
            $result = array_merge($result, $batch);

            if (count($batch) < 500) {
                return $result;
            }
        }
        throw ValidationException::withMessages(['delivery_point_code' => __('Не удалось получить полный список пунктов. Повторите позже.')]);
    }

    private function pointFitsPackages(CdekDeliveryPointResponseDto $point, City $city, array $packages): bool
    {
        if ($point->cityCode !== $city->cdek_code || $point->type !== 'PVZ') {
            return false;
        }

        foreach ($packages as $package) {
            if (($point->weightMin !== null && $package['weight'] < $point->weightMin * 1000)
                || ($point->weightMax !== null && $package['weight'] > $point->weightMax * 1000)) {
                return false;
            }

            if ($point->dimensions !== []) {
                $fits = false;
                $packageSizeCm = [(int) ceil($package['length'] / 10), (int) ceil($package['width'] / 10), (int) ceil($package['height'] / 10)];
                sort($packageSizeCm);

                foreach ($point->dimensions as $cell) {
                    $cellSizeCm = [$cell['width'], $cell['height'], $cell['depth']];
                    sort($cellSizeCm);

                    if ($packageSizeCm[0] <= $cellSizeCm[0] && $packageSizeCm[1] <= $cellSizeCm[1] && $packageSizeCm[2] <= $cellSizeCm[2]) {
                        $fits = true;
                        break;
                    }
                }

                if (! $fits) {
                    return false;
                }
            }
        }

        return true;
    }
}
