<?php

declare(strict_types=1);

namespace App\Services\Tbank;

use App\DTO\Tbank\TbankCancelPaymentRequestDto;
use App\DTO\Tbank\TbankCancelPaymentResponseDto;
use App\DTO\Tbank\TbankCheckOrderResponseDto;
use App\DTO\Tbank\TbankGetPaymentStateResponseDto;
use App\DTO\Tbank\TbankInitPaymentRequestDto;
use App\DTO\Tbank\TbankInitPaymentResponseDto;
use App\DTO\Tbank\TbankPaymentNotificationDto;
use App\Enums\Tbank\TbankPaymentStatusEnum;
use App\Services\Tbank\Exceptions\TbankApiException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class TbankService
{
    /**
     * Подключает API-клиент, преобразователь ответов и сервис подписи Т-Банка.
     */
    public function __construct(
        private readonly TbankClient $client,
        private readonly TbankResponseMapper $mapper,
        private readonly TbankSignatureService $signatures,
    ) {}

    /**
     * Создаёт платёж в Т-Банке и проверяет соответствие заказа и суммы.
     */
    public function initPayment(TbankInitPaymentRequestDto $payment): TbankInitPaymentResponseDto
    {
        $data = $payment->toArray();
        $data['NotificationURL'] ??= route('tbank.webhook');
        $result = $this->mapper->initPayment($this->client->post('Init', $data));

        if ($result->orderId !== $payment->orderId || ! $result->amount->equals($payment->amount)) {
            throw TbankApiException::invalidResponse();
        }

        return $result;
    }

    /**
     * Запрашивает отмену платежа или возврат оплаты и проверяет ответ Т-Банка.
     */
    public function cancelPayment(TbankCancelPaymentRequestDto $payment): TbankCancelPaymentResponseDto
    {
        $result = $this->mapper->cancelPayment($this->client->post('Cancel', $payment->toArray()));

        if ($result->paymentId !== $payment->paymentId
            || ($result->status !== TbankPaymentStatusEnum::CANCELED
                && $result->originalAmount !== null && ! $result->originalAmount->equals($payment->amount))
            || ($result->newAmount !== null && $result->newAmount->minorAmount() !== 0)) {
            throw TbankApiException::invalidResponse();
        }

        return $result;
    }

    /**
     * Получает состояние платежа и проверяет его идентификатор.
     */
    public function getPaymentState(string $paymentId): TbankGetPaymentStateResponseDto
    {
        TbankIdentifierValidator::validatePaymentId($paymentId);

        $result = $this->mapper->paymentState($this->client->post('GetState', ['PaymentId' => $paymentId]));

        if ($result->paymentId !== $paymentId) {
            throw TbankApiException::invalidResponse();
        }

        return $result;
    }

    /**
     * Получает платежи операции и проверяет идентификатор заказа.
     */
    public function checkOrder(string $orderId): TbankCheckOrderResponseDto
    {
        TbankIdentifierValidator::validateOrderId($orderId);

        $result = $this->mapper->checkOrder($this->client->post('CheckOrder', ['OrderId' => $orderId]));

        if ($result->orderId !== $orderId) {
            throw TbankApiException::invalidResponse();
        }

        return $result;
    }

    /**
     * Проверяет терминал и подпись уведомления, затем преобразует данные в DTO.
     */
    public function parsePaymentNotification(array $data): TbankPaymentNotificationDto
    {
        $terminalKey = config('tbank.terminal_key');

        if (! is_string($terminalKey) || $terminalKey === '') {
            throw TbankApiException::configuration('terminal_key');
        }

        if (($data['TerminalKey'] ?? null) !== $terminalKey || ! $this->signatures->verify($data)) {
            throw new AccessDeniedHttpException('Некорректная подпись или терминал уведомления Т-Банка.');
        }

        try {
            return $this->mapper->paymentNotification($data);
        } catch (TbankApiException) {
            throw ValidationException::withMessages(['notification' => 'Некорректные данные уведомления Т-Банка.']);
        }
    }
}
