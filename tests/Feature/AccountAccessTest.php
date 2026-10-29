<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\User;
use App\Support\Values\Money;

function createAccountUser(string $email, bool $emailVerified = true): User
{
    $user = User::query()->create([
        'first_name' => 'Покупатель',
        'email' => $email,
        'password' => 'password',
    ]);

    if ($emailVerified) {
        $user->markEmailAsVerified();
    }

    return $user;
}

function createAccountOrder(User $user, string $number): Order
{
    return Order::query()->create([
        'user_id' => $user->getKey(),
        'number' => $number,
        'status' => 'NEW',
        'payment_status' => 'PENDING',
        'delivery_method' => 'COURIER',
        'delivery_price' => Money::fromRubles(300),
        'total' => Money::fromRubles(1500),
        'customer_name' => 'Покупатель',
        'customer_phone' => '+7 (999) 123-45-67',
        'customer_email' => $user->email,
        'delivery_address' => ['city' => 'Владикавказ'],
    ]);
}

it('открывает профиль и список заказов авторизованному пользователю', function (): void {
    $user = createAccountUser('owner@example.com');

    $this->actingAs($user)->get(route('profile.show'))->assertOk();
    $this->actingAs($user)->get(route('orders.index'))->assertOk();
});

it('перенаправляет пользователя без подтвержденной почты на страницу подтверждения', function (): void {
    $user = createAccountUser('unverified@example.com', false);

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->get(route('orders.index'))
        ->assertRedirect(route('verification.notice'));
});

it('показывает пользователю только принадлежащий ему заказ', function (): void {
    $owner = createAccountUser('owner@example.com');
    $otherUser = createAccountUser('other@example.com');
    $ownOrder = createAccountOrder($owner, 'ORDER-001');
    $otherOrder = createAccountOrder($otherUser, 'ORDER-002');

    $this->actingAs($owner)
        ->get(route('orders.show', $ownOrder))
        ->assertOk()
        ->assertSee('ORDER-001');

    $this->actingAs($owner)
        ->get(route('orders.show', $otherOrder))
        ->assertNotFound();
});
