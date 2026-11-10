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

    private ?array $cachedItems = null;

    /**
     * Возвращает количества товаров в корзине по идентификаторам вариантов.
     */
    public function quantities(): array
    {
        if (! auth()->check()) {
            return session(self::SESSION_KEY, []);
        }

        return auth()->user()->cart()->first()?->items()->pluck('quantity', 'product_variant_id')->all() ?? [];
    }

    /**
     * Возвращает доступные товары корзины с данными для отображения.
     */
    public function items(): array
    {
        if ($this->cachedItems !== null) {
            return $this->cachedItems;
        }

        $quantities = $this->quantities();

        if ($quantities === []) {
            return $this->cachedItems = [];
        }

        $variants = ProductVariant::query()->with('product.media')->whereKey(array_keys($quantities))
            ->where('is_active', true)->whereHas('product', static fn ($query) => $query->available())->get();
        $items = [];

        foreach ($variants as $variant) {
            $quantity = (int) $quantities[$variant->id];
            $price = $variant->price;
            $items[] = [
                'id' => $variant->id,
                'name' => $variant->product->name,
                'label' => $variant->label(),
                'url' => route('products.show', [$variant->product, 'variant' => $variant->id]),
                'image' => $variant->product->getFirstMediaUrl('image', 'thumbnail'),
                'quantity' => $quantity,
                'price' => $price,
            ];
        }

        return $this->cachedItems = $items;
    }

    /**
     * Возвращает суммарное количество единиц товара в корзине.
     */
    public function count(): int
    {
        return array_sum(array_column($this->items(), 'quantity'));
    }

    /**
     * Рассчитывает стоимость товаров корзины.
     */
    public function total(): Money
    {
        $total = Money::fromMinor(0);

        foreach ($this->items() as $item) {
            $total = $total->add($item['price']->multiply($item['quantity']));
        }

        return $total;
    }

    /**
     * Добавляет доступный вариант товара в корзину с проверкой количества и совместимости.
     */
    public function add(int $variantId, int $quantity): void
    {
        $this->validateQuantity($quantity);

        $variant = ProductVariant::query()
            ->with('product')
            ->whereKey($variantId)
            ->where('is_active', true)
            ->whereHas('product', static fn ($query) => $query->available())
            ->firstOrFail();

        $quantities = $this->quantities();

        $this->assertCompatible($variant, $quantities);

        $current = (int) ($quantities[$variantId] ?? 0);

        $this->validateQuantity($current + $quantity);

        if (! auth()->check()) {
            $quantities[$variantId] = $current + $quantity;
            session()->put(self::SESSION_KEY, $quantities);
            $this->cachedItems = null;

            return;
        }

        DB::transaction(function () use ($variantId, $quantity): void {
            $cart = auth()->user()->cart()->firstOrCreate([]);
            $item = $cart->items()->firstOrCreate(['product_variant_id' => $variantId], ['quantity' => 0]);
            $item->increment('quantity', $quantity);
        });

        auth()->user()->unsetRelation('cart');

        $this->cachedItems = null;
    }

    /**
     * Меняет количество товара на единицу и возвращает новое значение.
     */
    public function changeQuantity(int $variantId, int $change): int
    {
        abort_unless(in_array($change, [-1, 1], true), 422);

        $quantities = $this->quantities();

        abort_unless(isset($quantities[$variantId]), 404);

        $quantity = (int) $quantities[$variantId] + $change;

        if ($quantity < 1) {
            $this->remove($variantId);

            return 0;
        }

        $this->validateQuantity($quantity);

        if (! auth()->check()) {
            $quantities[$variantId] = $quantity;
            session()->put(self::SESSION_KEY, $quantities);
        } else {
            auth()->user()->cart?->items()
                ->where('product_variant_id', $variantId)
                ->update(['quantity' => $quantity]);

            auth()->user()->unsetRelation('cart');
        }

        $this->cachedItems = null;

        return $quantity;
    }

    /**
     * Удаляет вариант товара из корзины.
     */
    public function remove(int $variantId): void
    {
        if (! auth()->check()) {
            $quantities = $this->quantities();
            unset($quantities[$variantId]);
            session()->put(self::SESSION_KEY, $quantities);
            $this->cachedItems = null;

            return;
        }

        auth()->user()->cart?->items()
            ->where('product_variant_id', $variantId)
            ->delete();

        auth()->user()->unsetRelation('cart');

        $this->cachedItems = null;
    }

    /**
     * Списывает заказанное количество товаров и обновляет кеш корзины.
     */
    public function removeOrderedItems(array $items): void
    {
        if (auth()->check()) {
            $cartData = auth()->user()->cart()->first();
            $cartItems = $cartData?->items()
                ->whereIn('product_variant_id', array_column($items, 'product_variant_id'))
                ->orderBy('product_variant_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_variant_id') ?? collect();

            foreach ($items as $item) {
                $cartItem = $cartItems->get($item['product_variant_id']);

                if ($cartItem !== null) {
                    $remaining = $cartItem->quantity - $item['quantity'];
                    $remaining > 0 ? $cartItem->update(['quantity' => $remaining]) : $cartItem->delete();
                }
            }
            auth()->user()->unsetRelation('cart');
        } else {
            $quantities = session(self::SESSION_KEY, []);

            foreach ($items as $item) {
                $remaining = (int) ($quantities[$item['product_variant_id']] ?? 0) - $item['quantity'];

                if ($remaining > 0) {
                    $quantities[$item['product_variant_id']] = $remaining;
                } else {
                    unset($quantities[$item['product_variant_id']]);
                }
            }
            session()->put(self::SESSION_KEY, $quantities);
        }
        $this->cachedItems = null;
    }

    /**
     * Проверяет, нужно ли предложить объединение гостевой корзины с корзиной пользователя.
     */
    public function shouldSuggestMerge(): bool
    {
        return auth()->check() && session(self::SESSION_KEY, []) !== []
            && session('cart.merge_declined') !== auth()->id();
    }

    /**
     * Переносит товары гостевой корзины в корзину пользователя и очищает гостевую корзину.
     */
    public function merge(): void
    {
        abort_unless(auth()->check(), 403);

        $guestItems = session(self::SESSION_KEY, []);

        $accountQuantities = $this->quantities();

        $variants = ProductVariant::query()->with('product')->whereKey(array_keys($guestItems))
            ->where('is_active', true)->whereHas('product', static fn ($query) => $query->available())->get();

        foreach ($variants as $variant) {
            $this->assertCompatible($variant, $accountQuantities);
            $quantity = (int) ($accountQuantities[$variant->id] ?? 0) + (int) $guestItems[$variant->id];
            $this->validateQuantity($quantity);
            $accountQuantities[$variant->id] = $quantity;
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

        $this->cachedItems = null;
    }

    /**
     * Запоминает отказ пользователя от объединения корзин.
     */
    public function declineMerge(): void
    {
        abort_unless(auth()->check(), 403);
        session()->put('cart.merge_declined', auth()->id());
    }

    private function assertCompatible(ProductVariant $variant, array $quantities): void
    {
        if ($quantities === []) {
            return;
        }

        $hasConflict = ProductVariant::query()
            ->whereKey(array_keys($quantities))
            ->where('is_preorder', ! $variant->is_preorder)
            ->exists();

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
