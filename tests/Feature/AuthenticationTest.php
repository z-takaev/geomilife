<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

function authenticationUser(string $email, bool $verified = true): User
{
    $user = User::query()->create(['first_name' => 'Иван', 'last_name' => 'Иванов', 'email' => $email, 'password' => 'password']);
    if ($verified) {
        $user->markEmailAsVerified();
    }

    return $user;
}

it('регистрирует пользователя с нормализованной почтой и отправляет подтверждение', function (): void {
    Notification::fake();

    $this->post(route('register'), [
        'first_name' => ' Иван ', 'last_name' => ' Иванов ', 'email' => ' BUYER@EXAMPLE.COM ',
        'password' => 'Password123', 'password_confirmation' => 'Password123', 'terms' => true,
        'email_verified_at' => now(), 'is_blocked' => true,
    ])->assertRedirect(route('verification.notice'));

    $user = User::query()->sole();
    $this->assertAuthenticatedAs($user);
    expect($user->first_name)->toBe('Иван')
        ->and($user->last_name)->toBe('Иванов')
        ->and($user->email)->toBe('buyer@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->is_blocked)->toBeFalse()
        ->and(Hash::check('Password123', $user->password))->toBeTrue();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('отклоняет некорректную регистрацию без создания аккаунта', function (array $changes, string $field): void {
    Notification::fake();
    $this->post(route('register'), array_replace([
        'first_name' => 'Иван', 'last_name' => 'Иванов', 'email' => 'buyer@example.com',
        'password' => 'Password123', 'password_confirmation' => 'Password123', 'terms' => true,
    ], $changes))->assertSessionHasErrorsIn('signUp', $field);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    Notification::assertNothingSent();
})->with([
    'нет согласия' => [['terms' => false], 'terms'],
    'нет имени' => [['first_name' => '  '], 'first_name'],
    'короткий пароль' => [['password' => 'Ab1', 'password_confirmation' => 'Ab1'], 'password'],
    'нет регистра' => [['password' => 'password123', 'password_confirmation' => 'password123'], 'password'],
    'разные пароли' => [['password_confirmation' => 'Other123'], 'password'],
    'неверная почта' => [['email' => 'invalid'], 'email'],
]);

it('не регистрирует повторно нормализованный email', function (): void {
    authenticationUser('buyer@example.com');
    $this->post(route('register'), [
        'first_name' => 'Иван', 'last_name' => 'Иванов', 'email' => ' BUYER@example.com ',
        'password' => 'Password123', 'password_confirmation' => 'Password123', 'terms' => true,
    ])->assertSessionHasErrorsIn('signUp', 'email');
    $this->assertDatabaseCount('users', 1);
    $this->assertGuest();
});

it('выполняет вход с нормализацией почты и переходом на запрошенную страницу', function (): void {
    $user = authenticationUser('buyer@example.com');
    $this->withSession(['url.intended' => route('orders.index')])
        ->post(route('login'), ['email' => ' BUYER@example.com ', 'password' => 'password'])
        ->assertRedirect(route('orders.index'));
    $this->assertAuthenticatedAs($user);
});

it('не авторизует с неверными данными или заблокированным аккаунтом', function (bool $blocked, string $password): void {
    $user = authenticationUser('buyer@example.com');
    $user->update(['is_blocked' => $blocked]);
    $this->post(route('login'), ['email' => $user->email, 'password' => $password])
        ->assertSessionHasErrorsIn('signIn', 'email');
    $this->assertGuest();
})->with([[false, 'wrong-password'], [true, 'password']]);

it('ограничивает количество попыток входа', function (): void {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login'), ['email' => 'missing@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrorsIn('signIn', 'email');
    }
    $this->post(route('login'), ['email' => 'missing@example.com', 'password' => 'wrong'])->assertTooManyRequests();
    $this->assertGuest();
});

it('при выходе завершает сессию и удаляет пользовательские данные', function (): void {
    $user = authenticationUser('buyer@example.com');
    $this->actingAs($user)->withSession(['city' => ['id' => 123]])
        ->post(route('logout'))->assertRedirect(route('home'))->assertSessionMissing('city');
    $this->assertGuest();
});

it('завершает активную сессию заблокированного пользователя', function (): void {
    $user = authenticationUser('buyer@example.com');
    $user->update(['is_blocked' => true]);
    $this->actingAs($user)->get(route('profile.show'))
        ->assertRedirect(route('home'))->assertSessionHasErrorsIn('signIn', 'email');
    $this->assertGuest();
});

it('подтверждает почту по подписанной ссылке', function (): void {
    Event::fake([Verified::class]);
    $user = authenticationUser('buyer@example.com', false);
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);
    $this->actingAs($user)->get($url)->assertRedirect(route('profile.show'));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->is($user));
});

it('не подтверждает почту по просроченной или чужой ссылке', function (bool $expired, bool $foreign): void {
    $user = authenticationUser('buyer@example.com', false);
    $other = authenticationUser('other@example.com', false);
    $target = $foreign ? $other : $user;
    $url = URL::temporarySignedRoute('verification.verify', $expired ? now()->subMinute() : now()->addMinutes(30), [
        'id' => $target->id, 'hash' => sha1($target->email),
    ]);
    $this->actingAs($user)->get($url)->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($other->fresh()->hasVerifiedEmail())->toBeFalse();
})->with([[true, false], [false, true]]);

it('повторно отправляет подтверждение только неподтвержденному пользователю', function (bool $verified): void {
    Notification::fake();
    $user = authenticationUser('buyer@example.com', $verified);
    $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
    if ($verified) {
        Notification::assertNothingSent();
    } else {
        Notification::assertSentTo($user, VerifyEmail::class);
    }
})->with([true, false]);

it('отправляет ссылку сброса без раскрытия существования аккаунта', function (bool $exists): void {
    Notification::fake();
    $user = $exists ? authenticationUser('buyer@example.com') : null;
    $this->post(route('password.email'), ['email' => ' BUYER@example.com '])
        ->assertRedirect()->assertSessionHas('auth_modal', 'forgotPassword')->assertSessionHas('auth_status');
    if ($user !== null) {
        Notification::assertSentTo($user, ResetPassword::class);
    } else {
        Notification::assertNothingSent();
    }
})->with([true, false]);

it('сбрасывает пароль одноразовым токеном и обновляет remember token', function (): void {
    $user = authenticationUser('buyer@example.com');
    $oldRememberToken = $user->remember_token;
    $token = Password::createToken($user);
    $payload = ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'];

    $this->post(route('password.update'), $payload)->assertRedirect(route('home'));
    expect(Hash::check('NewPassword123', $user->fresh()->password))->toBeTrue()
        ->and($user->fresh()->remember_token)->not->toBe($oldRememberToken);
    $this->assertGuest();
    $this->post(route('password.update'), $payload)->assertSessionHasErrorsIn('resetPassword', 'email');
});

it('не меняет пароль с неверным токеном или подтверждением', function (string $token, string $confirmation, string $field): void {
    $user = authenticationUser('buyer@example.com');
    $hash = $user->password;
    $this->post(route('password.update'), [
        'email' => $user->email, 'token' => $token, 'password' => 'NewPassword123', 'password_confirmation' => $confirmation,
    ])->assertSessionHasErrorsIn('resetPassword', $field);
    expect($user->fresh()->password)->toBe($hash);
})->with([['invalid-token', 'NewPassword123', 'email'], ['invalid-token', 'Different123', 'password']]);
