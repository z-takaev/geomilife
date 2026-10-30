<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Favorites\FavoriteService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class Wishlist extends Component
{
    public array $guestIds = [];

    public function initialize(array $guestIds, FavoriteService $favorites): void
    {
        $this->guestIds = $favorites->availableGuestIds($guestIds);

        if ($favorites->shouldSuggestMerge($this->guestIds)) {
            if ($favorites->ids() === []) {
                $this->merge($favorites);

                return;
            }

            $this->dispatch('favorites-merge-suggested');
        }

        if (auth()->check()) {
            $this->skipRender();
        }

        $this->dispatch('favorites-updated', ids: $favorites->ids($this->guestIds));
    }

    public function toggle(int $productId, FavoriteService $favorites): void
    {
        if (in_array($productId, $favorites->ids($this->guestIds), true)) {
            $ids = $favorites->remove($productId, $this->guestIds);
            $message = __('Товар удалён из избранного.');
        } else {
            $ids = $favorites->add($productId, $this->guestIds);
            $message = __('Товар добавлен в избранное.');
        }

        if (! auth()->check()) {
            $this->guestIds = $ids;
        }

        $this->dispatch('notify', message: $message, type: 'success');
        $this->dispatch('favorites-updated', ids: $ids);
    }

    public function remove(int $productId, FavoriteService $favorites): void
    {
        $ids = $favorites->remove($productId, $this->guestIds);

        if (! auth()->check()) {
            $this->guestIds = $ids;
        }

        $this->dispatch('favorites-updated', ids: $ids);
        $this->dispatch('notify', message: __('Товар удалён из избранного.'), type: 'success');
    }

    public function merge(FavoriteService $favorites): void
    {
        $favorites->merge($this->guestIds);
        $this->guestIds = [];
        $this->dispatch('favorites-merged');
        $this->dispatch('favorites-merge-resolved');
        $this->dispatch('favorites-updated', ids: $favorites->ids());
        $this->dispatch('notify', message: __('Товары добавлены в избранное.'), type: 'success');
    }

    public function decline(FavoriteService $favorites): void
    {
        $favorites->declineMerge();
        $this->skipRender();
        $this->dispatch('favorites-merge-resolved');
    }

    public function render(FavoriteService $favorites): View
    {
        $items = $favorites->items($this->guestIds);
        $ids = $favorites->ids($this->guestIds);
        $authenticated = auth()->check();

        return view('livewire.wishlist', compact('items', 'ids', 'authenticated'));
    }
}
