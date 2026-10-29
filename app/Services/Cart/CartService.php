<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\ProductVariant;
use App\Support\Values\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CartService
{
    private const string SESSION_KEY = 'cart.items';

    private ?array $cachedDetails = null;

    public function items(): array
    {
        if (! auth()->check()) {
            return session(self::SESSION_KEY, []);
        }

        return auth()->user()->cart?->items->pluck('quantity', 'product_variant_id')->all() ?? [];
    }

    public function details(): array
    {
        if ($this->cachedDetails !== null) {
            return $this->cachedDetails;
        }

        $quantities = $this->items();
        if ($quantities === []) {
            return $this->cachedDetails = ['items' => [], 'count' => 0, 'total' => Money::fromMinor(0)->format()];
        }

        $variants = ProductVariant::query()->with('product.media')->whereKey(array_keys($quantities))
            ->where('is_active', true)->whereHas('product', static fn ($query) => $query->available())->get();
        $total = Money::fromMinor(0);
        $count = 0;
        $items = [];
        foreach ($variants as $variant) {
            $quantity = (int) $quantities[$variant->id];
            $price = $variant->currentPrice();
            $total = $total->add($price->multiply($quantity));
            $count += $quantity;
            $items[] = [
                'id' => $variant->id,
                'name' => $variant->product->name,
                'label' => $variant->label(),
                'url' => route('products.show', [$variant->product, 'variant' => $variant->id]),
                'image' => $variant->product->getFirstMediaUrl('image', 'cart'),
                'quantity' => $quantity,
                'price' => $price->format(),
            ];
        }

        return $this->cachedDetails = ['items' => $items, 'count' => $count, 'total' => $total->format()];
    }

    public function add(int $variantId, int $quantity): void
    {
        $this->validateQuantity($quantity);
        $variant = ProductVariant::query()->with('product')->whereKey($variantId)->where('is_active', true)
            ->whereHas('product', static fn ($query) => $query->available())->firstOrFail();
        $items = $this->items();
        $this->assertCompatible($variant, $items);
        $current = (int) ($items[$variantId] ?? 0);
        $this->validateQuantity($current + $quantity);

        if (! auth()->check()) {
            $items[$variantId] = $current + $quantity;
            session()->put(self::SESSION_KEY, $items);
            $this->cachedDetails = null;

            return;
        }

        DB::transaction(function () use ($variantId, $quantity): void {
            $cart = auth()->user()->cart()->firstOrCreate([]);
            $item = $cart->items()->firstOrCreate(['product_variant_id' => $variantId], ['quantity' => 0]);
            $item->increment('quantity', $quantity);
        });
        auth()->user()->unsetRelation('cart');
        $this->cachedDetails = null;
    }

    public function remove(int $variantId): void
    {
        if (! auth()->check()) {
            $items = $this->items();
            unset($items[$variantId]);
            session()->put(self::SESSION_KEY, $items);
            $this->cachedDetails = null;

            return;
        }

        auth()->user()->cart?->items()->where('product_variant_id', $variantId)->delete();
        auth()->user()->unsetRelation('cart');
        $this->cachedDetails = null;
    }

    public function shouldSuggestMerge(): bool
    {
        return auth()->check() && session(self::SESSION_KEY, []) !== []
            && session('cart.merge_declined') !== auth()->id();
    }

    public function merge(): void
    {
        abort_unless(auth()->check(), 403);
        $guestItems = session(self::SESSION_KEY, []);
        $accountItems = $this->items();
        $variants = ProductVariant::query()->with('product')->whereKey(array_keys($guestItems))
            ->where('is_active', true)->whereHas('product', static fn ($query) => $query->available())->get();

        foreach ($variants as $variant) {
            $this->assertCompatible($variant, $accountItems);
            $this->validateQuantity((int) ($accountItems[$variant->id] ?? 0) + (int) $guestItems[$variant->id]);
        }

        DB::transaction(function () use ($variants, $guestItems): void {
            $cart = auth()->user()->cart()->firstOrCreate([]);
            foreach ($variants as $variant) {
                $item = $cart->items()->firstOrCreate(['product_variant_id' => $variant->id], ['quantity' => 0]);
                $item->increment('quantity', (int) $guestItems[$variant->id]);
            }
        });
        session()->forget([self::SESSION_KEY, 'cart.merge_declined']);
        auth()->user()->unsetRelation('cart');
        $this->cachedDetails = null;
    }

    public function declineMerge(): void
    {
        abort_unless(auth()->check(), 403);
        session()->put('cart.merge_declined', auth()->id());
    }

    private function assertCompatible(ProductVariant $variant, array $items): void
    {
        if ($items === []) {
            return;
        }
        $hasConflict = ProductVariant::query()->whereKey(array_keys($items))
            ->where('is_preorder', ! $variant->is_preorder)->exists();
        if ($hasConflict) {
            throw ValidationException::withMessages(['cart' => __('В корзине нельзя смешивать товары в наличии и под заказ.')]);
        }
    }

    private function validateQuantity(int $quantity): void
    {
        if ($quantity < 1 || $quantity > 1000) {
            throw ValidationException::withMessages(['quantity' => __('Количество должно быть от 1 до 1000.')]);
        }
    }
}
