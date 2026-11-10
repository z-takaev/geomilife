<?php

declare(strict_types=1);

namespace App\Services\Tbank;

use App\Services\Tbank\Exceptions\TbankApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class TbankClient
{
    public function __construct(private readonly TbankSignatureService $signatures) {}

    public function post(string $method, array $data): array
    {
        $baseUrl = $this->stringConfig('base_url');

        if (filter_var($baseUrl, FILTER_VALIDATE_URL) === false || parse_url($baseUrl, PHP_URL_SCHEME) !== 'https') {
            throw TbankApiException::configuration('base_url');
        }

        $data['TerminalKey'] = $this->stringConfig('terminal_key');
        $data['Token'] = $this->signatures->generate($data);

        $request = Http::acceptJson();

        if (app()->environment('local') && str_ends_with($data['TerminalKey'], 'DEMO')) {
            $request->withoutVerifying();
        }

        try {
            $response = $request
                ->connectTimeout($this->integerConfig('connect_timeout'))
                ->timeout($this->integerConfig('timeout'))
                ->post(rtrim($baseUrl, '/').'/'.ltrim($method, '/'), $data);
        } catch (ConnectionException $exception) {
            throw TbankApiException::connection($exception);
        }

        $result = $response->json();

        if (! $response->successful()) {
            throw $this->apiException($response->status(), is_array($result) ? $result : []);
        }

        if (! is_array($result) || ! is_bool($result['Success'] ?? null)
            || (! is_string($result['ErrorCode'] ?? null) && ! is_int($result['ErrorCode'] ?? null))) {
            throw TbankApiException::invalidResponse();
        }

        if (! $result['Success'] || (string) $result['ErrorCode'] !== '0') {
            throw $this->apiException($response->status(), $result);
        }

        return $result;
    }

    private function apiException(int $statusCode, array $data): TbankApiException
    {
        $errorCode = $data['ErrorCode'] ?? null;

        return new TbankApiException(
            message: is_string($data['Message'] ?? null) ? $data['Message'] : 'API Т-Банка вернул ошибку.',
            statusCode: $statusCode,
            errorCode: is_string($errorCode) || is_int($errorCode) ? (string) $errorCode : null,
            details: is_string($data['Details'] ?? null) ? $data['Details'] : null,
        );
    }

    private function stringConfig(string $key): string
    {
        $value = config("tbank.{$key}");

        if (! is_string($value) || trim($value) === '') {
            throw TbankApiException::configuration($key);
        }

        return $value;
    }

    private function integerConfig(string $key): int
    {
        $value = config("tbank.{$key}");

        if (! is_int($value) || $value < 1) {
            throw TbankApiException::configuration($key);
        }

        return $value;
    }
}
