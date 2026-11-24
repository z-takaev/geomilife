<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\ProductVariant;
use App\Support\Values\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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
        if (! Auth::check()) {
            return session(self::SESSION_KEY, []);
        }

        return Auth::user()
            ->cart()
            ->first()
            ?->items()
            ->pluck('quantity', 'product_variant_id')
            ->all() ?? [];
    }

    /**
     * Возвращает товары корзины для отображения.
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

        $variants = ProductVariant::query()
            ->with('product.media')
            ->whereKey(array_keys($quantities))
            ->available()->get();

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
                'total' => $price->multiply($quantity),
            ];
        }

        return $this->cachedItems = $items;
    }

    /**
     * Возвращает суммарное количество единиц товара в корзине.
     */
    public function count(): int
    {
        $quantities = $this->quantities();

        if ($quantities === []) {
            return 0;
        }

        $availableVariantIds = ProductVariant::query()
            ->whereKey(array_keys($quantities))
            ->available()
            ->pluck('id');

        return $availableVariantIds->sum(
            static fn (int $variantId): int => (int) $quantities[$variantId],
        );
    }

    /**
     * Проверяет наличие хотя бы одного доступного товара в корзине.
     */
    public function hasItems(): bool
    {
        if ($this->cachedItems !== null) {
            return $this->cachedItems !== [];
        }

        if (! Auth::check()) {
            $variantIds = array_keys(session(self::SESSION_KEY, []));

            return $variantIds !== [] && ProductVariant::query()
                ->whereKey($variantIds)
                ->available()
                ->exists();
        }

        return ProductVariant::query()
            ->available()
            ->whereHas('cartItems.cart', static fn (Builder $query): Builder => $query
                ->where('user_id', Auth::id()))
            ->exists();
    }

    /**
     * Рассчитывает стоимость товаров корзины.
     */
    public function total(): Money
    {
        $total = Money::fromMinor(0);

        foreach ($this->items() as $item) {
            $total = $total->add($item['total']);
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
            ->available()
            ->firstOrFail();

        $quantities = $this->quantities();

        $this->assertCompatible($variant, $quantities);

        $current = (int) ($quantities[$variantId] ?? 0);

        $this->validateQuantity($current + $quantity);

        if (! Auth::check()) {
            $quantities[$variantId] = $current + $quantity;
            session()->put(self::SESSION_KEY, $quantities);
            $this->cachedItems = null;

            return;
        }

        DB::transaction(function () use ($variantId, $quantity): void {
            $cart = Auth::user()->cart()->firstOrCreate([]);
            $item = $cart->items()->firstOrCreate(['product_variant_id' => $variantId], ['quantity' => 0]);
            $item->increment('quantity', $quantity);
        });

        Auth::user()->unsetRelation('cart');

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

        if (! Auth::check()) {
            $quantities[$variantId] = $quantity;
            session()->put(self::SESSION_KEY, $quantities);
        } else {
            Auth::user()->cart?->items()
                ->where('product_variant_id', $variantId)
                ->update(['quantity' => $quantity]);

            Auth::user()->unsetRelation('cart');
        }

        $this->cachedItems = null;

        return $quantity;
    }

    /**
     * Удаляет вариант товара из корзины.
     */
    public function remove(int $variantId): void
    {
        if (! Auth::check()) {
            $quantities = $this->quantities();
            unset($quantities[$variantId]);
            session()->put(self::SESSION_KEY, $quantities);
            $this->cachedItems = null;

            return;
        }

        Auth::user()->cart?->items()
            ->where('product_variant_id', $variantId)
            ->delete();

        Auth::user()->unsetRelation('cart');

        $this->cachedItems = null;
    }

    /**
     * Списывает заказанное количество товаров и обновляет кеш корзины.
     *
     * @param  array<int, int>  $quantitiesByVariant
     */
    public function removeOrderedItems(array $quantitiesByVariant): void
    {
        if (auth()->check()) {
            $cartData = auth()->user()->cart()->first();
            $cartItems = $cartData?->items()
                ->whereIn('product_variant_id', array_keys($quantitiesByVariant))
                ->orderBy('product_variant_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_variant_id') ?? collect();

            foreach ($quantitiesByVariant as $variantId => $quantity) {
                $cartItem = $cartItems->get($variantId);

                if ($cartItem !== null) {
                    $remaining = $cartItem->quantity - $quantity;
                    $remaining > 0 ? $cartItem->update(['quantity' => $remaining]) : $cartItem->delete();
                }
            }
            auth()->user()->unsetRelation('cart');
        } else {
            $quantities = session(self::SESSION_KEY, []);

            foreach ($quantitiesByVariant as $variantId => $quantity) {
                $remaining = (int) ($quantities[$variantId] ?? 0) - $quantity;

                if ($remaining > 0) {
                    $quantities[$variantId] = $remaining;
                } else {
                    unset($quantities[$variantId]);
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
        return Auth::check() && session(self::SESSION_KEY, []) !== []
            && session('cart.merge_declined') !== Auth::id();
    }

    /**
     * Переносит товары гостевой корзины в корзину пользователя и очищает гостевую корзину.
     */
    public function merge(): void
    {
        abort_unless(Auth::check(), 403);

        $guestQuantities = session(self::SESSION_KEY, []);

        $accountQuantities = $this->quantities();

        $variants = ProductVariant::query()
            ->with('product')
            ->whereKey(array_keys($guestQuantities))
            ->available()
            ->get();

        foreach ($variants as $variant) {
            $this->assertCompatible($variant, $accountQuantities);
            $quantity = (int) ($accountQuantities[$variant->id] ?? 0) + (int) $guestQuantities[$variant->id];
            $this->validateQuantity($quantity);
            $accountQuantities[$variant->id] = $quantity;
        }

        DB::transaction(function () use ($variants, $guestQuantities): void {
            $cart = Auth::user()->cart()->firstOrCreate([]);

            foreach ($variants as $variant) {
                $item = $cart->items()->firstOrCreate(['product_variant_id' => $variant->id], ['quantity' => 0]);
                $item->increment('quantity', (int) $guestQuantities[$variant->id]);
            }
        });

        session()->forget([self::SESSION_KEY, 'cart.merge_declined']);

        Auth::user()->unsetRelation('cart');

        $this->cachedItems = null;
    }

    /**
     * Запоминает отказ пользователя от объединения корзин.
     */
    public function declineMerge(): void
    {
        abort_unless(Auth::check(), 403);
        session()->put('cart.merge_declined', Auth::id());
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
