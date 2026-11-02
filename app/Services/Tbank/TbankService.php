<?php

declare(strict_types=1);

namespace App\Services\Tbank;

use App\DTO\Tbank\Requests\CheckOrderDto;
use App\DTO\Tbank\Requests\GetPaymentStateDto;
use App\DTO\Tbank\Requests\InitPaymentDto;
use App\DTO\Tbank\Responses\TbankCheckOrderResponseDto;
use App\DTO\Tbank\Responses\TbankInitPaymentResponseDto;
use App\DTO\Tbank\Responses\TbankPaymentNotificationDto;
use App\DTO\Tbank\Responses\TbankPaymentStateResponseDto;
use App\Services\Tbank\Exceptions\TbankApiException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class TbankService
{
    public function __construct(
        private readonly TbankClient $client,
        private readonly TbankResponseMapper $mapper,
        private readonly TbankTokenService $tokens,
    ) {}

    public function initPayment(InitPaymentDto $payment): TbankInitPaymentResponseDto
    {
        $data = $payment->toArray();
        $data['NotificationURL'] ??= route('tbank.webhook');
        $result = $this->mapper->initPayment($this->client->post('Init', $data));

        if ($result->orderId !== $payment->orderId || ! $result->amount->equals($payment->amount)) {
            throw TbankApiException::invalidResponse();
        }

        return $result;
    }

    public function getPaymentState(GetPaymentStateDto $payment): TbankPaymentStateResponseDto
    {
        $result = $this->mapper->paymentState($this->client->post('GetState', $payment->toArray()));

        if ($result->paymentId !== $payment->paymentId) {
            throw TbankApiException::invalidResponse();
        }

        return $result;
    }

    public function checkOrder(CheckOrderDto $order): TbankCheckOrderResponseDto
    {
        $result = $this->mapper->checkOrder($this->client->post('CheckOrder', $order->toArray()));

        if ($result->orderId !== $order->orderId) {
            throw TbankApiException::invalidResponse();
        }

        return $result;
    }

    public function parsePaymentNotification(array $data): TbankPaymentNotificationDto
    {
        $terminalKey = config('tbank.terminal_key');

        if (! is_string($terminalKey) || $terminalKey === '') {
            throw TbankApiException::configuration('terminal_key');
        }

        if (($data['TerminalKey'] ?? null) !== $terminalKey || ! $this->tokens->verify($data)) {
            throw new AccessDeniedHttpException('Некорректная подпись или терминал уведомления Т-Банка.');
        }

        try {
            return $this->mapper->paymentNotification($data);
        } catch (TbankApiException) {
            throw ValidationException::withMessages(['notification' => 'Некорректные данные уведомления Т-Банка.']);
        }
    }
}
