<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\DTO\Orders\CheckoutPresentationDto;
use App\DTO\Orders\CheckoutQuoteDto;
use Illuminate\Support\Facades\Cache;

final class CheckoutStateService
{
    public function save(string $idempotencyKey, CheckoutQuoteDto $quote, CheckoutPresentationDto $presentation): void
    {
        Cache::put(
            $this->key($idempotencyKey),
            ['quote' => $quote->toArray(), 'presentation' => $presentation->toArray()],
            (int) config('cache.items.checkout_state.ttl'),
        );
    }

    public function getQuote(string $idempotencyKey): ?CheckoutQuoteDto
    {
        $state = Cache::get($this->key($idempotencyKey));

        return is_array($state) ? CheckoutQuoteDto::tryFromArray($state['quote'] ?? null) : null;
    }

    public function getPresentation(string $idempotencyKey): ?CheckoutPresentationDto
    {
        $state = Cache::get($this->key($idempotencyKey));

        return is_array($state) ? CheckoutPresentationDto::tryFromArray($state['presentation'] ?? null) : null;
    }

    public function invalidateQuote(string $idempotencyKey): void
    {
        $state = Cache::get($this->key($idempotencyKey));

        if (! is_array($state)) {
            return;
        }

        $presentation = CheckoutPresentationDto::tryFromArray($state['presentation'] ?? null);

        if ($presentation === null) {
            return;
        }

        $state['quote'] = null;
        $state['presentation'] = $presentation->withoutReadyDelivery()->toArray();
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
