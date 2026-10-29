<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\Address;
use App\Models\City;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class ManageProfile extends Component
{
    public string $firstName = '';

    public string $lastName = '';

    public ?string $birthDate = null;

    public ?string $phone = null;

    public ?int $addressId = null;

    public ?int $cityId = null;

    public string $street = '';

    public string $house = '';

    public ?string $building = null;

    public ?string $apartment = null;

    public ?string $entrance = null;

    public ?string $floor = null;

    public ?string $intercom = null;

    public bool $isDefault = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->firstName = $user->first_name;
        $this->lastName = $user->last_name ?? '';
        $this->birthDate = $user->birth_date?->format('Y-m-d');
        $this->phone = $user->phone;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $validated = $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['nullable', 'string', 'max:255'],
            'birthDate' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:255', Rule::unique('users', 'phone')->ignore($user->getKey())],
        ]);
        $user->update(['first_name' => $validated['firstName'], 'last_name' => $validated['lastName'], 'birth_date' => $validated['birthDate'], 'phone' => $validated['phone']]);
        $this->dispatch('notify', message: __('Данные профиля сохранены.'), type: 'success');
    }

    public function editAddress(int $addressId): void
    {
        $address = auth()->user()->addresses()->findOrFail($addressId);
        $this->resetValidation();
        $this->addressId = $address->id;
        $this->cityId = $address->city_id;
        $this->street = $address->street ?? '';
        $this->house = $address->house ?? '';
        $this->building = $address->building;
        $this->apartment = $address->apartment;
        $this->entrance = $address->entrance;
        $this->floor = $address->floor;
        $this->intercom = $address->intercom;
        $this->isDefault = $address->is_default;
    }

    public function saveAddress(): void
    {
        $user = auth()->user();
        $validated = $this->validate([
            'addressId' => ['nullable', 'integer', Rule::exists('addresses', 'id')->where('user_id', $user->getKey())],
            'cityId' => ['required', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'street' => ['nullable', 'string', 'max:255'],
            'house' => ['nullable', 'string', 'max:255', 'required_with:street'],
            'building' => ['nullable', 'string', 'max:255'],
            'apartment' => ['nullable', 'string', 'max:255'],
            'entrance' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'intercom' => ['nullable', 'string', 'max:255'],
            'isDefault' => ['boolean'],
        ]);
        DB::transaction(function () use ($user, $validated): void {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            if ($validated['isDefault']) {
                $user->addresses()->update(['is_default' => false]);
            }
            $address = $validated['addressId'] ? $user->addresses()->findOrFail($validated['addressId']) : new Address;
            $address->fill([
                'city_id' => $validated['cityId'], 'street' => $validated['street'], 'house' => $validated['house'],
                'building' => $validated['building'], 'apartment' => $validated['apartment'], 'entrance' => $validated['entrance'],
                'floor' => $validated['floor'], 'intercom' => $validated['intercom'],
                'is_default' => $validated['isDefault'] || $address->is_default || ! $user->addresses()->exists(),
            ]);
            $user->addresses()->save($address);
            if ($address->is_default) {
                $user->city()->associate($address->city_id);
                $user->save();
            }
        });
        $this->resetAddressForm();
        $this->dispatch('notify', message: $validated['addressId'] === null ? __('Адрес добавлен.') : __('Адрес сохранён.'), type: 'success');
        $this->dispatch('address-saved');
    }

    public function setDefaultAddress(int $addressId): void
    {
        $user = auth()->user();
        DB::transaction(function () use ($user, $addressId): void {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $address = $user->addresses()->findOrFail($addressId);
            $user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
            $user->city()->associate($address->city_id);
            $user->save();
        });
        $this->dispatch('notify', message: __('Активный адрес выбран.'), type: 'success');
    }

    public function deleteAddress(int $addressId): void
    {
        $user = auth()->user();
        DB::transaction(function () use ($user, $addressId): void {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $address = $user->addresses()->findOrFail($addressId);
            $wasDefault = $address->is_default;
            $address->delete();
            if ($wasDefault) {
                $nextAddress = $user->addresses()->orderBy('id')->first();
                $nextAddress?->update(['is_default' => true]);
                $user->city()->associate($nextAddress?->city_id);
                $user->save();
            }
        });
        if ($this->addressId === $addressId) {
            $this->resetAddressForm();
        }
    }

    public function resetAddressForm(): void
    {
        $this->resetValidation();
        $this->reset(['addressId', 'cityId', 'street', 'house', 'building', 'apartment', 'entrance', 'floor', 'intercom', 'isDefault']);
    }

    public function connectTelegram(): ?string
    {
        $username = (string) config('telegram.bot_username');
        if ($username === '' || blank(config('telegram.bot_token'))) {
            $this->addError('telegram', __('Telegram-бот пока не настроен.'));

            return null;
        }
        $token = Str::random(64);
        auth()->user()->forceFill(['telegram_link_token' => $token])->save();

        return 'https://t.me/'.ltrim($username, '@').'?start='.$token;
    }

    public function disconnectTelegram(): void
    {
        auth()->user()->forceFill(['telegram_chat_id' => null, 'telegram_username' => null, 'telegram_link_token' => null, 'telegram_notifications_enabled' => false])->save();
    }

    public function addAddress(): void
    {
        $this->resetAddressForm();
    }

    public function render(): View
    {
        $user = auth()->user();
        $addresses = $user->addresses()->with('city')->orderByDesc('is_default')->latest()->get();
        $cities = City::query()->where('is_active', true)->orderBy('name')->get();
        $telegramBotConfigured = filled(config('telegram.bot_username')) && filled(config('telegram.bot_token'));

        return view('livewire.profile.manage-profile', compact('user', 'addresses', 'cities', 'telegramBotConfigured'));
    }
}
