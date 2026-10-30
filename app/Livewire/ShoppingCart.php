<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Cart\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class ShoppingCart extends Component
{
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
            $this->dispatch('cart-updated', count: $cart->count());
            $this->dispatch('notify', message: __('Товар добавлен в корзину.'), type: 'success');

            return true;
        } catch (ValidationException $exception) {
            $this->skipRender();
            $this->dispatch('notify', message: collect($exception->errors())->flatten()->first(), type: 'error');

            return false;
        }
    }

    public function remove(int $variantId, CartService $cart): void
    {
        $cart->remove($variantId);
        $this->dispatch('cart-updated', count: $cart->count());
        $this->dispatch('notify', message: __('Товар удалён из корзины.'), type: 'success');
    }

    public function merge(CartService $cart): void
    {
        try {
            $cart->merge();
            $this->dispatch('cart-updated', count: $cart->count());
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

    public function render(CartService $cart): View
    {
        $items = $cart->items();
        $count = $cart->count();
        $total = $cart->total();

        return view('livewire.shopping-cart', compact('items', 'count', 'total'));
    }
}
