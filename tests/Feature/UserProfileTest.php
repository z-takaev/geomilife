<?php

declare(strict_types=1);

use App\Livewire\UserProfile;
use App\Models\City;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::query()->create(['first_name' => 'Иван', 'last_name' => 'Иванов', 'email' => 'buyer@example.com', 'password' => 'Password123']);
    $this->city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    $this->actingAs($this->user);
});

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

it('делает первый адрес основным и синхронизирует город профиля', function (): void {
    Livewire::test(UserProfile::class)->set('city_id', $this->city->id)
        ->set('street', 'Ленина')->set('house', '10')->call('saveAddress')
        ->assertHasNoErrors()->assertSet('city_id', null)->assertSet('street', '')
        ->assertDispatched('address-saved');
    $address = $this->user->addresses()->sole();
    expect($address->is_default)->toBeTrue()
        ->and($address->street)->toBe('Ленина')
        ->and($address->house)->toBe('10')
        ->and($this->user->fresh()->city_id)->toBe($this->city->id);
});

it('редактирует адрес без создания дубля и сохраняет его основным', function (): void {
    $address = $this->user->addresses()->create(['city_id' => $this->city->id, 'street' => 'Старая', 'house' => '1', 'is_default' => true]);
    Livewire::test(UserProfile::class)->call('editAddress', $address->id)
        ->assertSet('street', 'Старая')->set('street', 'Новая')->set('is_default', false)
        ->call('saveAddress')->assertHasNoErrors();
    expect($address->fresh()->street)->toBe('Новая')->and($address->fresh()->is_default)->toBeTrue();
    $this->assertDatabaseCount('addresses', 1);
});

it('при смене основного адреса снимает прежний и обновляет город', function (string $operation): void {
    $old = $this->user->addresses()->create(['city_id' => $this->city->id, 'is_default' => true]);
    $city = City::query()->create(['cdek_code' => 137, 'name' => 'Санкт-Петербург', 'is_active' => true]);
    $component = Livewire::test(UserProfile::class);
    if ($operation === 'create') {
        $component->set('city_id', $city->id)->set('is_default', true)->call('saveAddress')->assertHasNoErrors();
    } else {
        $address = $this->user->addresses()->create(['city_id' => $city->id]);
        $component->call('setDefaultAddress', $address->id);
    }
    expect($old->fresh()->is_default)->toBeFalse()
        ->and($this->user->addresses()->where('is_default', true)->count())->toBe(1)
        ->and($this->user->fresh()->city_id)->toBe($city->id);
})->with(['create', 'select']);

it('не меняет основной адрес при добавлении дополнительного', function (): void {
    $old = $this->user->addresses()->create(['city_id' => $this->city->id, 'is_default' => true]);
    $this->user->update(['city_id' => $this->city->id]);
    $city = City::query()->create(['cdek_code' => 137, 'name' => 'Санкт-Петербург', 'is_active' => true]);
    Livewire::test(UserProfile::class)->set('city_id', $city->id)->call('saveAddress')->assertHasNoErrors();
    expect($old->fresh()->is_default)->toBeTrue()
        ->and($this->user->addresses()->where('city_id', $city->id)->sole()->is_default)->toBeFalse()
        ->and($this->user->fresh()->city_id)->toBe($this->city->id);
});

it('после удаления основного адреса выбирает оставшийся или очищает город', function (bool $hasAnother): void {
    $address = $this->user->addresses()->create(['city_id' => $this->city->id, 'is_default' => true]);
    $this->user->update(['city_id' => $this->city->id]);
    $other = $hasAnother ? $this->user->addresses()->create(['city_id' => $this->city->id]) : null;
    Livewire::test(UserProfile::class)->call('editAddress', $address->id)
        ->call('deleteAddress', $address->id)->assertSet('address_id', null);
    $this->assertModelMissing($address);
    expect($this->user->fresh()->city_id)->toBe($other?->city_id);
    if ($other !== null) {
        expect($other->fresh()->is_default)->toBeTrue();
    }
})->with([true, false]);

it('не позволяет читать, удалять и выбирать чужой адрес', function (string $method): void {
    $other = User::query()->create(['first_name' => 'Другой', 'email' => 'other@example.com', 'password' => 'Password123']);
    $address = $other->addresses()->create(['city_id' => $this->city->id, 'street' => 'Чужая', 'is_default' => true]);
    Livewire::test(UserProfile::class)->call($method, $address->id)->assertNotFound();
    expect($address->fresh()->street)->toBe('Чужая')->and($address->fresh()->is_default)->toBeTrue();
    expect($this->user->addresses()->count())->toBe(0);
})->with(['editAddress', 'deleteAddress', 'setDefaultAddress']);

it('отклоняет подмену идентификатора адреса при сохранении', function (): void {
    $other = User::query()->create(['first_name' => 'Другой', 'email' => 'other@example.com', 'password' => 'Password123']);
    $address = $other->addresses()->create(['city_id' => $this->city->id, 'street' => 'Чужая']);
    Livewire::test(UserProfile::class)->set('address_id', $address->id)
        ->set('city_id', $this->city->id)->set('street', 'Подмена')->set('house', '1')
        ->call('saveAddress')->assertHasErrors(['address_id' => 'exists']);
    expect($address->fresh()->street)->toBe('Чужая');
});

it('проверяет доступность города и номер дома', function (): void {
    $this->city->update(['is_active' => false]);
    Livewire::test(UserProfile::class)->set('city_id', $this->city->id)->set('street', 'Ленина')
        ->call('saveAddress')->assertHasErrors(['city_id' => 'exists', 'house' => 'required_with']);
    $this->assertDatabaseCount('addresses', 0);
});
