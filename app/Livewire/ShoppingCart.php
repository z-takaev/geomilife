<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Cart\CartService;
use App\Support\Values\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

// TODO: Заменить название
final class ShoppingCart extends Component
{
    public bool $items_loaded = false;

    public function mount(CartService $cart): void
    {
        if ($cart->shouldSuggestMerge()) {
            $this->dispatch('cart-merge-suggested');
        }
    }

    public function add(int $variantId, int $quantity, CartService $cart): bool
    {
        try {
            $cart->add($variantId, $quantity);
            $this->items_loaded = true;
            $this->dispatch('cart-updated', hasItems: $cart->hasItems());
            $this->dispatch('notify', message: __('Товар добавлен в корзину.'), type: 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->skipRender();
            $this->dispatch('notify', message: collect($exception->errors())->flatten()->first(), type: 'error');

            return false;
        }
    }

    public function changeQuantity(int $variantId, int $change, CartService $cart): void
    {
        try {
            $quantity = $cart->changeQuantity($variantId, $change);
            $this->items_loaded = true;
            $this->dispatch('cart-updated', hasItems: $cart->hasItems());

            if ($quantity === 0) {
                $this->dispatch('notify', message: __('Товар удалён из корзины.'), type: 'success');
            }
        } catch (ValidationException $exception) {
            $this->skipRender();
            $this->dispatch('notify', message: collect($exception->errors())->flatten()->first(), type: 'error');
        }
    }

    public function remove(int $variantId, CartService $cart): void
    {
        $cart->remove($variantId);
        $this->items_loaded = true;
        $this->dispatch('cart-updated', hasItems: $cart->hasItems());
        $this->dispatch('notify', message: __('Товар удалён из корзины.'), type: 'success');
    }

    public function merge(CartService $cart): void
    {
        try {
            $cart->merge();
            $this->items_loaded = true;
            $this->dispatch('cart-updated', hasItems: $cart->hasItems());
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
        $count = $this->items_loaded ? array_sum(array_column($items, 'quantity')) : 0;
        $hasItems = $this->items_loaded ? $items !== [] : $cart->hasItems();
        $total = $this->items_loaded ? $cart->total() : Money::fromMinor(0);

        return view('livewire.shopping-cart', compact('items', 'count', 'hasItems', 'total'));
    }
}
