<?php

declare(strict_types=1);

namespace App\DTO\Tbank;

use App\Services\Tbank\TbankIdentifierValidator;
use App\Support\Values\Money;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class TbankInitPaymentRequestDto
{
    public function __construct(
        public string $orderId,
        public Money $amount,
        public ?string $description = null,
        public ?string $successUrl = null,
        public ?string $failUrl = null,
        public ?string $notificationUrl = null,
        public ?DateTimeImmutable $redirectDueDate = null,
    ) {
        TbankIdentifierValidator::validateOrderId($orderId);

        if ($amount->minorAmount() < 1) {
            throw new InvalidArgumentException('Сумма платежа должна быть больше нуля.');
        }

        if ($description !== null && (trim($description) === '' || mb_strlen($description) > 140)) {
            throw new InvalidArgumentException('Описание платежа должно содержать от 1 до 140 символов.');
        }

        foreach ([$successUrl, $failUrl, $notificationUrl] as $url) {
            if ($url !== null && (filter_var($url, FILTER_VALIDATE_URL) === false
                || parse_url($url, PHP_URL_SCHEME) !== 'https' || mb_strlen($url) > 250)) {
                throw new InvalidArgumentException('Укажите корректный HTTPS URL длиной до 250 символов.');
            }
        }

        if ($redirectDueDate !== null && ($redirectDueDate < new DateTimeImmutable('+1 minute')
            || $redirectDueDate > new DateTimeImmutable('+90 days'))) {
            throw new InvalidArgumentException('Срок действия ссылки должен быть от 1 минуты до 90 дней.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'OrderId' => $this->orderId,
            'Amount' => $this->amount->minorAmount(),
            'Description' => $this->description,
            'SuccessURL' => $this->successUrl,
            'FailURL' => $this->failUrl,
            'NotificationURL' => $this->notificationUrl,
            'RedirectDueDate' => $this->redirectDueDate?->format('Y-m-d\TH:i:sP'),
            'DATA' => ['OperationInitiatorType' => '0'],
        ], static fn (mixed $value): bool => $value !== null);
    }
}
