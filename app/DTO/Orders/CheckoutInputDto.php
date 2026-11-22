<?php

declare(strict_types=1);

namespace App\DTO\Orders;

use App\Enums\Orders\DeliveryMethodEnum;
use App\Enums\Orders\PaymentMethodEnum;

final readonly class CheckoutInputDto
{
    public function __construct(
        public ?int $cityId,
        public ?int $addressId,
        public ?DeliveryMethodEnum $deliveryMethod,
        public ?PaymentMethodEnum $paymentMethod,
        public string $firstName,
        public string $lastName,
        public string $phone,
        public string $email,
        public string $comment,
        public string $street,
        public string $house,
        public ?string $building,
        public ?string $apartment,
        public ?string $entrance,
        public ?string $floor,
        public ?string $intercom,
        public bool $deliveryToDoor,
        public string $deliveryPointCode,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cityId: self::nullableInteger($data['city_id'] ?? null),
            addressId: self::nullableInteger($data['address_id'] ?? null),
            deliveryMethod: DeliveryMethodEnum::tryFrom((string) ($data['delivery_method'] ?? '')),
            paymentMethod: PaymentMethodEnum::tryFrom((string) ($data['payment_method'] ?? '')),
            firstName: (string) ($data['first_name'] ?? ''),
            lastName: (string) ($data['last_name'] ?? ''),
            phone: (string) ($data['phone'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            comment: (string) ($data['comment'] ?? ''),
            street: (string) ($data['street'] ?? ''),
            house: (string) ($data['house'] ?? ''),
            building: self::nullableString($data['building'] ?? null),
            apartment: self::nullableString($data['apartment'] ?? null),
            entrance: self::nullableString($data['entrance'] ?? null),
            floor: self::nullableString($data['floor'] ?? null),
            intercom: self::nullableString($data['intercom'] ?? null),
            deliveryToDoor: (bool) ($data['delivery_to_door'] ?? false),
            deliveryPointCode: (string) ($data['delivery_point_code'] ?? ''),
        );
    }

    private static function nullableInteger(mixed $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT);

        return $integer === false ? null : $integer;
    }

    private static function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }
}
