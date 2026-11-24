<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RepeatOrderAction
{
    public function execute(User $user, Order $order): void
    {
        if ($order->user_id !== $user->id) {
            throw new AuthorizationException;
        }

        $quantities = $order->items()
            ->selectRaw('product_variant_id, SUM(quantity) AS quantity')
            ->groupBy('product_variant_id')
            ->pluck('quantity', 'product_variant_id')
            ->map(static fn (mixed $quantity): int => (int) $quantity)
            ->all();

        if ($quantities === [] || collect($quantities)->contains(static fn (int $quantity): bool => $quantity < 1 || $quantity > 1000)) {
            throw ValidationException::withMessages(['order' => __('Не удалось восстановить состав заказа.')]);
        }

        $variants = ProductVariant::query()
            ->with('product')
            ->whereKey(array_keys($quantities))
            ->available()
            ->get();

        if ($variants->count() !== count($quantities) || $variants->pluck('is_preorder')->unique()->count() > 1) {
            throw ValidationException::withMessages(['order' => __('Некоторые товары из заказа больше недоступны.')]);
        }

        DB::transaction(function () use ($user, $quantities): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $cart = $user->cart()->firstOrCreate([]);
            $cart->items()->delete();
            $cart->items()->createMany(collect($quantities)
                ->map(static fn (int $quantity, int $variantId): array => [
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                ])->values()->all());
        });

        $user->unsetRelation('cart');
    }
}
