<?php

declare(strict_types=1);

use App\DTO\Tbank\TbankCancelPaymentRequestDto;
use App\DTO\Tbank\TbankInitPaymentRequestDto;
use App\Services\Tbank\TbankService;
use App\Support\Values\Money;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    config(['tbank.base_url' => 'https://bank.test', 'tbank.terminal_key' => 'TEST', 'tbank.password' => 'secret']);
    Http::preventStrayRequests();
});

it('сохраняет допустимый идентификатор платежа в запросах статуса и отмены', function (string $paymentId): void {
    Http::fake(['bank.test/GetState' => Http::response([
        'Success' => true, 'ErrorCode' => '0', 'PaymentId' => $paymentId,
        'OrderId' => 'order-1', 'Amount' => 100, 'Status' => 'NEW',
    ])]);

    expect(app(TbankService::class)->getPaymentState($paymentId)->paymentId)->toBe($paymentId)
        ->and((new TbankCancelPaymentRequestDto(paymentId: $paymentId, amount: Money::fromMinor(100)))->toArray())
        ->toBe(['PaymentId' => $paymentId, 'Amount' => 100]);
    Http::assertSent(fn (Request $request): bool => $request['PaymentId'] === $paymentId);
})->with(['1', '000123', str_repeat('9', 20)]);

it('отклоняет некорректный идентификатор платежа в запросах статуса и отмены', function (string $paymentId): void {
    $message = 'Укажите корректный идентификатор платежа Т-Банка.';

    expect(fn () => app(TbankService::class)->getPaymentState($paymentId))->toThrow(InvalidArgumentException::class, $message);
    expect(fn () => new TbankCancelPaymentRequestDto(paymentId: $paymentId, amount: Money::fromMinor(100)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with(['', ' ', '123abc', '-1', '1.5', "123\n", ' 123', '１２３', str_repeat('9', 21)]);

it('сохраняет допустимый идентификатор операции в запросах проверки и оплаты', function (string $orderId): void {
    Http::fake(['bank.test/CheckOrder' => Http::response([
        'Success' => true, 'ErrorCode' => '0', 'OrderId' => $orderId, 'Payments' => [],
    ])]);

    expect(app(TbankService::class)->checkOrder($orderId)->orderId)->toBe($orderId)
        ->and((new TbankInitPaymentRequestDto(orderId: $orderId, amount: Money::fromMinor(100)))->toArray())
        ->toBe(['OrderId' => $orderId, 'Amount' => 100, 'DATA' => ['OperationInitiatorType' => '0']]);
    Http::assertSent(fn (Request $request): bool => $request['OrderId'] === $orderId);
})->with(['1', 'order-123', str_repeat('я', 50)]);

it('отклоняет некорректный идентификатор операции в запросах проверки и оплаты', function (string $orderId): void {
    $message = 'Идентификатор операции должен содержать от 1 до 50 символов.';

    expect(fn () => app(TbankService::class)->checkOrder($orderId))->toThrow(InvalidArgumentException::class, $message);
    expect(fn () => new TbankInitPaymentRequestDto(orderId: $orderId, amount: Money::fromMinor(100)))
        ->toThrow(InvalidArgumentException::class, $message);
})->with(['', ' ', "\t\n", str_repeat('я', 51)]);
