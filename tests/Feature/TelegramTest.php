<?php

declare(strict_types=1);

use App\Livewire\UserProfile;
use App\Models\User;
use App\Services\Telegram\TelegramService;
use Livewire\Livewire;
use SergiX44\Nutgram\Nutgram;

it('создает новую ссылку подключения и делает предыдущий токен недействительным', function (): void {
    config(['telegram.bot_username' => '@test_bot', 'telegram.bot_token' => 'test-token']);
    $user = User::query()->create(['first_name' => 'Иван', 'email' => 'buyer@example.com', 'password' => 'Password123']);
    $service = app(TelegramService::class);
    $url = $service->createConnectionUrl($user);
    $token = $user->fresh()->telegram_link_token;
    expect($token)->toHaveLength(64)->and($url)->toBe('https://t.me/test_bot?start='.$token);
    $service->createConnectionUrl($user);
    expect($user->fresh()->telegram_link_token)->not->toBe($token);
});

it('отключает уведомления и очищает реквизиты Telegram', function (): void {
    $user = User::query()->create(['first_name' => 'Иван', 'email' => 'buyer@example.com', 'password' => 'Password123']);
    $user->forceFill(['telegram_chat_id' => 123, 'telegram_username' => 'buyer',
        'telegram_link_token' => str_repeat('a', 64), 'telegram_notifications_enabled' => true])->save();
    Livewire::actingAs($user)->test(UserProfile::class)->call('disconnectTelegram');
    expect($user->fresh()->telegram_chat_id)->toBeNull()
        ->and($user->fresh()->telegram_username)->toBeNull()
        ->and($user->fresh()->telegram_link_token)->toBeNull()
        ->and($user->fresh()->telegram_notifications_enabled)->toBeFalse();
});

it('не создает ссылку подключения без настроенного бота', function (): void {
    config(['telegram.bot_token' => '', 'telegram.bot_username' => '']);
    $user = User::query()->create(['first_name' => 'Иван', 'email' => 'buyer@example.com', 'password' => 'Password123']);
    Livewire::actingAs($user)->test(UserProfile::class)->call('connectTelegram')->assertHasErrors('telegram');
    expect($user->fresh()->telegram_link_token)->toBeNull();
});

it('отклоняет webhook без конфигурации', function (string $token, string $secret): void {
    config(['telegram.bot_token' => $token, 'telegram.webhook_secret' => $secret]);
    $this->postJson(route('telegram.webhook'), ['update_id' => 1])->assertStatus(503);
})->with([['', 'secret'], ['token', '']]);

it('отклоняет webhook с отсутствующим или неверным секретом', function (string $secret): void {
    config(['telegram.bot_token' => 'token', 'telegram.webhook_secret' => 'expected-secret']);
    $this->postJson(route('telegram.webhook'), ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => $secret])->assertForbidden();
})->with(['', 'wrong-secret']);

it('проверяет формат входящего webhook после проверки секрета', function (): void {
    config(['telegram.bot_token' => 'token', 'telegram.webhook_secret' => 'secret']);
    $this->postJson(route('telegram.webhook'), ['update_id' => 'invalid'], ['X-Telegram-Bot-Api-Secret-Token' => 'secret'])
        ->assertUnprocessable()->assertJsonValidationErrors('update_id');
});

it('подключает Telegram одноразовым токеном только в личном чате', function (): void {
    $user = User::query()->create(['first_name' => 'Иван', 'email' => 'buyer@example.com', 'password' => 'Password123']);
    $token = str_repeat('a', 64);
    $user->forceFill(['telegram_link_token' => $token])->save();
    $bot = Nutgram::fake();
    $bot->onMessage([app(TelegramService::class), 'handleMessage']);
    $bot->hearMessage(['text' => '/start '.$token, 'chat' => ['id' => 123, 'type' => 'private'], 'from' => ['id' => 123, 'username' => 'buyer']])->reply();
    expect($user->fresh()->telegram_chat_id)->toBe(123)
        ->and($user->fresh()->telegram_username)->toBe('buyer')
        ->and($user->fresh()->telegram_link_token)->toBeNull()
        ->and($user->fresh()->telegram_notifications_enabled)->toBeTrue();
    $bot->hearMessage(['text' => '/start '.$token, 'chat' => ['id' => 456, 'type' => 'private']])->reply();
    expect($user->fresh()->telegram_chat_id)->toBe(123);
});

it('не подключает уведомления из группового чата', function (): void {
    $user = User::query()->create(['first_name' => 'Иван', 'email' => 'buyer@example.com', 'password' => 'Password123']);
    $token = str_repeat('a', 64);
    $user->forceFill(['telegram_link_token' => $token])->save();
    $bot = Nutgram::fake();
    $bot->onMessage([app(TelegramService::class), 'handleMessage']);
    $bot->hearMessage(['text' => '/start '.$token, 'chat' => ['id' => -123, 'type' => 'group']])->reply();
    expect($user->fresh()->telegram_chat_id)->toBeNull()
        ->and($user->fresh()->telegram_link_token)->toBe($token);
});
