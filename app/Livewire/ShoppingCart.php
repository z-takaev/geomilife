<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Cart\CartService;
use App\Support\Values\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class ShoppingCart extends Component
{
    public bool $items_loaded = false;

    public function mount(CartService $cart): void
    {
        if ($cart->shouldSuggestMerge()) {
            $this->dispatch('cart-merge-suggested');
        }
    }

    public function add(int $variantId, int $quantity, CartService $cart): array|false
    {
        try {
            $cart->add($variantId, $quantity);
            $this->items_loaded = true;
            $state = $cart->state();
            $this->dispatch('cart-updated', ...$state);
            $this->dispatch('notify', message: __('Товар добавлен в корзину.'), type: 'success');

            return $state;
        } catch (ValidationException $exception) {
            $this->skipRender();
            $this->dispatch('notify', message: collect($exception->errors())->flatten()->first(), type: 'error');

            return false;
        }
    }

    public function changeQuantity(int $variantId, int $change, CartService $cart): array|false
    {
        try {
            $quantity = $cart->changeQuantity($variantId, $change);
            $this->items_loaded = true;
            $state = $cart->state();
            $this->dispatch('cart-updated', ...$state);

            if ($quantity === 0) {
                $this->dispatch('notify', message: __('Товар удалён из корзины.'), type: 'success');
            }

            return $state;
        } catch (ValidationException $exception) {
            $this->skipRender();
            $this->dispatch('notify', message: collect($exception->errors())->flatten()->first(), type: 'error');

            return false;
        }
    }

    public function remove(int $variantId, CartService $cart): void
    {
        $cart->remove($variantId);
        $this->items_loaded = true;
        $this->dispatch('cart-updated', ...$cart->state());
        $this->dispatch('notify', message: __('Товар удалён из корзины.'), type: 'success');
    }

    public function merge(CartService $cart): void
    {
        try {
            $cart->merge();
            $this->items_loaded = true;
            $this->dispatch('cart-updated', ...$cart->state());
            $this->dispatch('cart-merge-resolved');
            $this->dispatch('notify', message: __('Корзины объединены.'), type: 'success');
        } catch (ValidationException $exception) {
            $this->skipRender();
            $this->dispatch('notify', message: collect($exception->errors())->flatten()->first(), type: 'error');
        }
    }

    public function decline(CartService $cart): void
    {
        $cart->declineMerge();
        $this->skipRender();
        $this->dispatch('cart-merge-resolved');
    }

    public function loadItems(): void
    {
        $this->items_loaded = true;
    }

    public function render(CartService $cart): View
    {
        $items = $this->items_loaded ? $cart->items() : [];
        $quantities = $this->items_loaded
            ? array_column($items, 'quantity', 'id')
            : $cart->availableQuantities();
        $count = array_sum($quantities);
        $hasItems = $count > 0;
        $total = $this->items_loaded ? $cart->total() : Money::fromMinor(0);

        return view('livewire.shopping-cart', compact('items', 'quantities', 'count', 'hasItems', 'total'));
    }
}
