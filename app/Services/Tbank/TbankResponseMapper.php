<?php

declare(strict_types=1);

namespace App\Services\Tbank;

use App\DTO\Tbank\Responses\TbankCheckOrderResponseDto;
use App\DTO\Tbank\Responses\TbankInitPaymentResponseDto;
use App\DTO\Tbank\Responses\TbankOrderPaymentResponseDto;
use App\DTO\Tbank\Responses\TbankPaymentNotificationDto;
use App\DTO\Tbank\Responses\TbankPaymentStateResponseDto;
use App\Enums\Tbank\TbankPaymentStatusEnum;
use App\Services\Tbank\Exceptions\TbankApiException;
use App\Support\Values\Money;

final class TbankResponseMapper
{
    public function initPayment(array $data): TbankInitPaymentResponseDto
    {
        $url = $this->string($data, 'PaymentURL');

        if (filter_var($url, FILTER_VALIDATE_URL) === false || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw TbankApiException::invalidResponse();
        }

        return new TbankInitPaymentResponseDto(
            paymentId: $this->identifier($data, 'PaymentId'),
            paymentUrl: $url,
            orderId: $this->string($data, 'OrderId'),
            amount: $this->amount($data),
            status: $this->status($data),
        );
    }

    public function paymentState(array $data): TbankPaymentStateResponseDto
    {
        return new TbankPaymentStateResponseDto(
            paymentId: $this->identifier($data, 'PaymentId'),
            orderId: $this->string($data, 'OrderId'),
            amount: $this->amount($data),
            status: $this->status($data),
        );
    }

    public function checkOrder(array $data): TbankCheckOrderResponseDto
    {
        $payments = $data['Payments'] ?? null;

        if (! is_array($payments) || ! array_is_list($payments)) {
            throw TbankApiException::invalidResponse();
        }

        $result = [];

        foreach ($payments as $payment) {
            if (! is_array($payment)) {
                throw TbankApiException::invalidResponse();
            }

            $result[] = new TbankOrderPaymentResponseDto(
                paymentId: $this->identifier($payment, 'PaymentId'),
                amount: $this->amount($payment),
                status: $this->status($payment),
            );
        }

        return new TbankCheckOrderResponseDto($this->string($data, 'OrderId'), $result);
    }

    public function paymentNotification(array $data): TbankPaymentNotificationDto
    {
        if (! is_bool($data['Success'] ?? null)) {
            throw TbankApiException::invalidResponse();
        }

        $errorCode = $data['ErrorCode'] ?? null;

        if ((! is_string($errorCode) && ! is_int($errorCode)) || (string) $errorCode === '') {
            throw TbankApiException::invalidResponse();
        }

        return new TbankPaymentNotificationDto(
            terminalKey: $this->string($data, 'TerminalKey'),
            paymentId: $this->identifier($data, 'PaymentId'),
            orderId: $this->string($data, 'OrderId'),
            amount: $this->amount($data),
            status: $this->status($data),
            success: $data['Success'],
            errorCode: (string) $errorCode,
        );
    }

    private function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw TbankApiException::invalidResponse();
        }

        return $value;
    }

    private function identifier(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if ((! is_string($value) && ! is_int($value)) || preg_match('/^[0-9]{1,20}$/D', (string) $value) !== 1) {
            throw TbankApiException::invalidResponse();
        }

        return (string) $value;
    }

    private function amount(array $data): Money
    {
        $value = $data['Amount'] ?? null;

        if (is_string($value) && preg_match('/^[0-9]+$/D', $value) === 1) {
            $value = filter_var(ltrim($value, '0') ?: '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        }

        if (! is_int($value) || $value < 0) {
            throw TbankApiException::invalidResponse();
        }

        return Money::fromMinor($value);
    }

    private function status(array $data): TbankPaymentStatusEnum
    {
        $status = TbankPaymentStatusEnum::tryFrom($this->string($data, 'Status'));

        return $status ?? throw TbankApiException::invalidResponse();
    }
}
