<?php

declare(strict_types=1);

namespace App\Services\Cdek\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

final class CdekApiException extends RuntimeException
{
    /**
     * @param  array<mixed>  $errors
     */
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly array $errors = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    public static function configuration(string $key): self
    {
        return new self("Не задана настройка СДЭК: services.cdek.{$key}.");
    }

    public static function connection(Throwable $exception): self
    {
        return new self('Не удалось подключиться к API СДЭК.', previous: $exception);
    }

    public static function invalidResponse(): self
    {
        return new self('API СДЭК вернул ответ в неожиданном формате.');
    }

    public static function fromResponse(Response $response): self
    {
        $data = $response->json();
        $errors = is_array($data) && is_array($data['errors'] ?? null)
            ? $data['errors']
            : [];
        $message = is_array($data) && is_string($data['error_description'] ?? null)
            ? $data['error_description']
            : self::firstErrorMessage($errors);

        return new self(
            $message ?? 'API СДЭК вернул ошибку.',
            $response->status(),
            $errors,
        );
    }

    /**
     * @param  array<mixed>  $errors
     */
    private static function firstErrorMessage(array $errors): ?string
    {
        $firstError = $errors[0] ?? null;

        if (! is_array($firstError)) {
            return null;
        }

        $message = $firstError['message'] ?? null;

        return is_string($message) ? $message : null;
    }
}
