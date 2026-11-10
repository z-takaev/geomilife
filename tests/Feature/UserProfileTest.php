<?php

declare(strict_types=1);

use App\Livewire\UserProfile;
use App\Models\City;
use App\Models\User;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::query()->create(['first_name' => 'Иван', 'last_name' => 'Иванов', 'email' => 'buyer@example.com', 'password' => 'Password123']);
    $this->city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    $this->actingAs($this->user);
});

it('не содержит методов добавления и редактирования адресов', function (string $method): void {
    expect(method_exists(UserProfile::class, $method))->toBeFalse();
})->with(['editAddress', 'saveAddress', 'addAddress']);

it('сохраняет профиль текущего пользователя', function (): void {
    Livewire::test(UserProfile::class)->assertSet('first_name', 'Иван')
        ->set('first_name', 'Пётр')->set('last_name', 'Петров')
        ->set('birth_date', '1990-01-01')->set('phone', '+79991234567')
        ->call('saveProfile')->assertHasNoErrors();
    expect($this->user->fresh()->only(['first_name', 'last_name', 'phone']))->toBe([
        'first_name' => 'Пётр', 'last_name' => 'Петров', 'phone' => '+79991234567',
    ])->and($this->user->fresh()->birth_date->format('Y-m-d'))->toBe('1990-01-01');
});

it('не сохраняет профиль с чужим телефоном или будущей датой рождения', function (): void {
    User::query()->create(['first_name' => 'Другой', 'email' => 'other@example.com', 'password' => 'Password123', 'phone' => '+79991234567']);
    Livewire::test(UserProfile::class)->set('first_name', 'Пётр')
        ->set('phone', '+79991234567')->set('birth_date', today()->addDay()->toDateString())
        ->call('saveProfile')->assertHasErrors(['phone' => 'unique', 'birth_date' => 'before']);
    expect($this->user->fresh()->first_name)->toBe('Иван');
});

it('позволяет сохранить собственный телефон без изменения', function (): void {
    $this->user->update(['phone' => '+79991234567']);
    Livewire::test(UserProfile::class)->call('saveProfile')->assertHasNoErrors();
});

it('не позволяет создавать адрес из профиля прямым вызовом', function (): void {
    $this->withoutExceptionHandling();
    expect(fn () => Livewire::test(UserProfile::class)->call('saveAddress'))
        ->toThrow(MethodNotFoundException::class);
    $this->assertDatabaseCount('addresses', 0);
});

it('не позволяет открывать редактирование адреса в профиле', function (): void {
    $address = $this->user->addresses()->create(['city_id' => $this->city->id, 'street' => 'Старая', 'house' => '1', 'is_default' => true]);
    $this->withoutExceptionHandling();
    expect(fn () => Livewire::test(UserProfile::class)->call('editAddress', $address->id))
        ->toThrow(MethodNotFoundException::class);
    expect($address->fresh()->street)->toBe('Старая');
});

it('не позволяет сохранять изменения адреса через профиль', function (): void {
    $address = $this->user->addresses()->create(['city_id' => $this->city->id, 'street' => 'Старая', 'house' => '1']);
    $this->withoutExceptionHandling();
    expect(fn () => Livewire::test(UserProfile::class)->call('saveAddress', $address->id))
        ->toThrow(MethodNotFoundException::class);
    expect($address->fresh()->street)->toBe('Старая')->and($address->fresh()->house)->toBe('1');
    $this->assertDatabaseCount('addresses', 1);
});

it('позволяет выбрать активный адрес и обновляет город', function (): void {
    $old = $this->user->addresses()->create(['city_id' => $this->city->id, 'is_default' => true]);
    $city = City::query()->create(['cdek_code' => 137, 'name' => 'Санкт-Петербург', 'is_active' => true]);
    $address = $this->user->addresses()->create(['city_id' => $city->id]);
    Livewire::test(UserProfile::class)->call('setDefaultAddress', $address->id);
    expect($old->fresh()->is_default)->toBeFalse()
        ->and($address->fresh()->is_default)->toBeTrue()
        ->and($this->user->addresses()->where('is_default', true)->count())->toBe(1)
        ->and($this->user->fresh()->city_id)->toBe($city->id);
});

it('в профиле нет действий создания и редактирования адресов', function (): void {
    $this->user->addresses()->create(['city_id' => $this->city->id, 'street' => 'Мира', 'house' => '1']);
    Livewire::test(UserProfile::class)->assertDontSee('Добавить адрес')->assertDontSee('Изменить адрес')
        ->assertDontSeeHtml('wire:click="editAddress(')->assertDontSeeHtml('wire:submit="saveAddress"')
        ->assertDontSeeHtml('wire:click="addAddress"');
});

it('после удаления активного адреса выбирает последний использованный или очищает город', function (bool $hasAnother): void {
    $address = $this->user->addresses()->create(['city_id' => $this->city->id, 'is_default' => true]);
    $this->user->update(['city_id' => $this->city->id]);
    $other = $hasAnother ? $this->user->addresses()->create(['city_id' => $this->city->id, 'last_used_at' => now()->subDay()]) : null;
    if ($hasAnother) {
        $this->user->addresses()->create(['city_id' => $this->city->id, 'last_used_at' => now()->subDays(3)]);
    }
    Livewire::test(UserProfile::class)->call('deleteAddress', $address->id);
    $this->assertModelMissing($address);
    expect($this->user->fresh()->city_id)->toBe($other?->city_id);
    if ($other !== null) {
        expect($other->fresh()->is_default)->toBeTrue();
    }
})->with([true, false]);

it('не позволяет удалять и выбирать чужой адрес', function (string $method): void {
    $other = User::query()->create(['first_name' => 'Другой', 'email' => 'other@example.com', 'password' => 'Password123']);
    $address = $other->addresses()->create(['city_id' => $this->city->id, 'street' => 'Чужая', 'is_default' => true]);
    Livewire::test(UserProfile::class)->call($method, $address->id)->assertNotFound();
    expect($address->fresh()->street)->toBe('Чужая')->and($address->fresh()->is_default)->toBeTrue();
    expect($this->user->addresses()->count())->toBe(0);
})->with(['deleteAddress', 'setDefaultAddress']);

it('не позволяет подменить идентификатор адреса при запрещённом сохранении', function (): void {
    $other = User::query()->create(['first_name' => 'Другой', 'email' => 'other@example.com', 'password' => 'Password123']);
    $address = $other->addresses()->create(['city_id' => $this->city->id, 'street' => 'Чужая']);
    $this->withoutExceptionHandling();
    expect(fn () => Livewire::test(UserProfile::class)->call('saveAddress', $address->id))
        ->toThrow(MethodNotFoundException::class);
    expect($address->fresh()->street)->toBe('Чужая');
});
