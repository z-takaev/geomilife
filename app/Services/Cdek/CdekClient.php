<?php

declare(strict_types=1);

namespace App\Services\Cdek;

use App\Services\Cdek\Exceptions\CdekApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class CdekClient
{
    private readonly string $baseUrl;

    private readonly string $clientId;

    private readonly string $clientSecret;

    private readonly int $connectTimeout;

    private readonly int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim($this->stringConfig('base_url'), '/');
        $this->clientId = $this->stringConfig('client_id');
        $this->clientSecret = $this->stringConfig('client_secret');
        $this->connectTimeout = $this->integerConfig('connect_timeout');
        $this->timeout = $this->integerConfig('timeout');
    }

    /**
     * @param  array<string, int|string>  $query
     * @return array<mixed>
     */
    public function get(string $uri, array $query = []): array
    {
        $response = $this->sendGetRequest($uri, $query);

        if ($response->unauthorized()) {
            Cache::forget($this->tokenCacheKey());
            $response = $this->sendGetRequest($uri, $query);
        }

        return $this->responseData($response);
    }

    /**
     * @param  array<string, int|string>  $query
     */
    private function sendGetRequest(string $uri, array $query): Response
    {
        try {
            return Http::baseUrl($this->baseUrl)
                ->acceptJson()
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->withToken($this->accessToken())
                ->get($uri, $query);
        } catch (ConnectionException $exception) {
            throw CdekApiException::connection($exception);
        }
    }

    private function accessToken(): string
    {
        $cachedToken = Cache::get($this->tokenCacheKey());

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->acceptJson()
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->withQueryParameters([
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ])
                ->post('/v2/oauth/token');
        } catch (ConnectionException $exception) {
            throw CdekApiException::connection($exception);
        }

        $data = $this->responseData($response);
        $accessToken = $data['access_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? null;

        if (! is_string($accessToken) || $accessToken === '' || ! is_int($expiresIn)) {
            throw CdekApiException::invalidResponse();
        }

        Cache::put(
            $this->tokenCacheKey(),
            $accessToken,
            max(1, $expiresIn - 60),
        );

        return $accessToken;
    }

    /**
     * @return array<mixed>
     */
    private function responseData(Response $response): array
    {
        if ($response->failed()) {
            throw CdekApiException::fromResponse($response);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw CdekApiException::invalidResponse();
        }

        return $data;
    }

    private function tokenCacheKey(): string
    {
        return 'cdek.oauth_token.'.hash('sha256', $this->baseUrl.'|'.$this->clientId);
    }

    private function stringConfig(string $key): string
    {
        $value = config("services.cdek.{$key}");

        if (! is_string($value) || $value === '') {
            throw CdekApiException::configuration($key);
        }

        return $value;
    }

    private function integerConfig(string $key): int
    {
        $value = config("services.cdek.{$key}");

        if (! is_int($value) || $value < 1) {
            throw CdekApiException::configuration($key);
        }

        return $value;
    }
}
