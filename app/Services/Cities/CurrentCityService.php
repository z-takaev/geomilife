<?php

declare(strict_types=1);

namespace App\Services\Cities;

use App\Models\City;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;

final class CurrentCityService
{
    private array $cities = [];

    /**
     * Возвращает выбранный активный город из профиля, сессии или cookie.
     */
    public function current(): ?City
    {
        $cityId = auth()->user()?->city_id ?? session('city.id') ?? request()->cookie('city_id');

        if ($cityId === null) {
            return null;
        }

        $cityId = (int) $cityId;

        if (! array_key_exists($cityId, $this->cities)) {
            $this->cities[$cityId] = City::query()->where('is_active', true)->find($cityId);
        }

        return $this->cities[$cityId];
    }

    /**
     * Сохраняет выбранный активный город в профиле, сессии и cookie.
     */
    public function select(int $cityId): City
    {
        $city = City::query()->where('is_active', true)->findOrFail($cityId);
        $user = auth()->user();

        if ($user !== null) {
            $user->city()->associate($city);
            $user->save();
        }

        $this->remember($city);

        return $city;
    }

    /**
     * Возвращает выбранный город или определяет и запоминает город по IP-адресу.
     */
    public function detect(): ?City
    {
        $currentCity = $this->current();

        if ($currentCity !== null || auth()->user()?->city_id !== null) {
            return $currentCity;
        }

        $city = $this->detectByIp(request()->ip());

        if ($city !== null) {
            $this->remember($city);
        }

        return $city;
    }

    private function remember(City $city): void
    {
        session()->put('city', ['id' => $city->getKey()]);
        Cookie::queue(Cookie::forever('city_id', (string) $city->getKey()));

        $this->cities[$city->getKey()] = $city;
    }

    private function detectByIp(?string $ip): ?City
    {
        if ($ip === null || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null;
        }

        $cacheKey = 'ipwhois:city:'.hash('sha256', $ip);
        $result = Cache::get($cacheKey);

        if ($result === null) {
            if (Cache::has('ipwhois:rate-limited')) {
                return null;
            }

            $city = $this->requestCity($ip);
            $result = ['id' => $city?->getKey()];

            Cache::put($cacheKey, $result, $city === null
                ? (int) config('services.ipwhois.failure_cache_ttl')
                : (int) config('services.ipwhois.success_cache_ttl'));

            return $city;
        }

        return $result['id'] === null
            ? null
            : City::query()->where('is_active', true)->find($result['id']);
    }

    private function requestCity(string $ip): ?City
    {
        try {
            $response = Http::connectTimeout((int) config('services.ipwhois.connect_timeout'))
                ->timeout((int) config('services.ipwhois.timeout'))
                ->get(config('services.ipwhois.base_url').'/'.$ip, [
                    'lang' => 'ru',
                    'fields' => 'success,country_code,city',
                ]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->tooManyRequests()) {
            $retryAfter = $response->header('Retry-After');
            $seconds = ctype_digit($retryAfter)
                ? (int) $retryAfter
                : (int) config('services.ipwhois.success_cache_ttl');

            Cache::put('ipwhois:rate-limited', true, max(1, min($seconds, 86400)));

            return null;
        }

        if (! $response->successful() || $response->json('success') !== true || $response->json('country_code') !== 'RU') {
            return null;
        }

        $name = $response->json('city');

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        $cities = City::query()
            ->where('is_active', true)
            ->whereLike('name', addcslashes(trim($name), '\\%_'))
            ->limit(2)
            ->get();

        return $cities->count() === 1 ? $cities->first() : null;
    }
}
