<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\DTO\Orders\CheckoutInputDto;
use App\DTO\Orders\CheckoutPresentationDto;
use App\DTO\Orders\CheckoutQuoteDto;
use App\Enums\Orders\DeliveryMethodEnum;
use App\Services\Orders\CheckoutService;
use App\Services\Orders\CheckoutStateService;

final readonly class CalculateCheckoutQuoteAction
{
    public function __construct(
        private CheckoutService $checkout,
        private CheckoutStateService $state,
    ) {}

    public function execute(CheckoutInputDto $input, string $idempotencyKey): CheckoutPresentationDto
    {
        $calculation = $this->checkout->calculateOrder($input);
        $deliveryMethod = $input->deliveryMethod?->value ?? '';
        $deliveryReady = $calculation->city !== null
            && array_key_exists($deliveryMethod, $this->checkout->availableDeliveryMethods($calculation->city))
            && ($deliveryMethod !== DeliveryMethodEnum::CDEK->value || $calculation->point !== null);
        $presentation = CheckoutPresentationDto::fromCalculation($calculation, $deliveryReady);

        $this->state->save(
            $idempotencyKey,
            new CheckoutQuoteDto($calculation->fingerprint, $calculation->total),
            $presentation,
        );

        return $presentation;
    }
}
