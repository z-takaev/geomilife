<?php

declare(strict_types=1);

namespace App\Services\Orders;

use Illuminate\Support\Facades\Cache;

final class CheckoutStateService
{
    public function save(string $idempotencyKey, array $quote, array $presentation): void
    {
        Cache::put(
            $this->key($idempotencyKey),
            compact('quote', 'presentation'),
            (int) config('cache.items.checkout_state.ttl'),
        );
    }

    public function getQuote(string $idempotencyKey): ?array
    {
        $state = Cache::get($this->key($idempotencyKey));

        return is_array($state) && is_array($state['quote'] ?? null) ? $state['quote'] : null;
    }

    public function getPresentation(string $idempotencyKey): array
    {
        $state = Cache::get($this->key($idempotencyKey));

        return is_array($state) && is_array($state['presentation'] ?? null) ? $state['presentation'] : [];
    }

    public function invalidateQuote(string $idempotencyKey): void
    {
        $state = Cache::get($this->key($idempotencyKey));

        if (! is_array($state) || ! is_array($state['presentation'] ?? null)) {
            return;
        }

        $state['quote'] = null;
        $state['presentation']['delivery_ready'] = false;
        Cache::put(
            $this->key($idempotencyKey),
            $state,
            (int) config('cache.items.checkout_state.ttl'),
        );
    }

    public function forget(string $idempotencyKey): void
    {
        Cache::forget($this->key($idempotencyKey));
    }

    private function key(string $idempotencyKey): string
    {
        $sessionHash = hash_hmac('sha256', session()->getId(), (string) config('app.key'));

        return 'checkout:state:'.$sessionHash.':'.$idempotencyKey;
    }
}
