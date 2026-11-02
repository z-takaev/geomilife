<?php

declare(strict_types=1);

namespace App\Services\Tbank\Exceptions;

use RuntimeException;
use Throwable;

final class TbankApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $details = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function configuration(string $key): self
    {
        return new self("Не задана или некорректна настройка Т-Банка: tbank.{$key}.");
    }

    public static function connection(Throwable $exception): self
    {
        return new self('Не удалось получить ответ API Т-Банка. Результат операции неизвестен.', previous: $exception);
    }

    public static function invalidResponse(): self
    {
        return new self('API Т-Банка вернул ответ в неожиданном формате.');
    }
}
