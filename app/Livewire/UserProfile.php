<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Address;
use App\Models\City;
use App\Services\Telegram\TelegramService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class UserProfile extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public ?string $birth_date = null;

    public ?string $phone = null;

    public ?int $address_id = null;

    public ?int $city_id = null;

    public string $street = '';

    public string $house = '';

    public ?string $building = null;

    public ?string $apartment = null;

    public ?string $entrance = null;

    public ?string $floor = null;

    public ?string $intercom = null;

    public bool $is_default = false;

    public function mount(): void
    {
        $user = auth()->user();

        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name ?? '';
        $this->birth_date = $user->birth_date?->format('Y-m-d');
        $this->phone = $user->phone;
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:255', Rule::unique('users', 'phone')->ignore($user->getKey())],
        ]);

        $user->update($validated);
        $this->dispatch('notify', message: __('Данные профиля сохранены.'), type: 'success');
    }

    public function editAddress(int $addressId): void
    {
        $address = auth()->user()->addresses()->findOrFail($addressId);
        $this->resetValidation();

        $this->address_id = $address->id;
        $this->city_id = $address->city_id;
        $this->street = $address->street ?? '';
        $this->house = $address->house ?? '';
        $this->building = $address->building;
        $this->apartment = $address->apartment;
        $this->entrance = $address->entrance;
        $this->floor = $address->floor;
        $this->intercom = $address->intercom;
        $this->is_default = $address->is_default;
    }

    public function saveAddress(): void
    {
        $user = auth()->user();
        $validated = $this->validate([
            'address_id' => ['nullable', 'integer', Rule::exists('addresses', 'id')->where('user_id', $user->getKey())],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'street' => ['nullable', 'string', 'max:255'],
            'house' => ['nullable', 'string', 'max:255', 'required_with:street'],
            'building' => ['nullable', 'string', 'max:255'],
            'apartment' => ['nullable', 'string', 'max:255'],
            'entrance' => ['nullable', 'string', 'max:255'],
            'floor' => ['nullable', 'string', 'max:255'],
            'intercom' => ['nullable', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($validated['is_default']) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address = $validated['address_id'] ? $user->addresses()->findOrFail($validated['address_id']) : new Address;

            $address->fill([
                'city_id' => $validated['city_id'],
                'street' => $validated['street'],
                'house' => $validated['house'],
                'building' => $validated['building'],
                'apartment' => $validated['apartment'],
                'entrance' => $validated['entrance'],
                'floor' => $validated['floor'],
                'intercom' => $validated['intercom'],
                'is_default' => $validated['is_default'] || $address->is_default || ! $user->addresses()->exists(),
            ]);

            $user->addresses()->save($address);

            if ($address->is_default) {
                $user->city()->associate($address->city_id);
                $user->save();
            }
        });

        $this->resetAddressForm();
        $this->dispatch('notify', message: __('Адрес сохранён.'), type: 'success');
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

        $this->dispatch('notify', message: __('Активный адрес обновлен.'), type: 'success');
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

        if ($this->address_id === $addressId) {
            $this->resetAddressForm();
        }
    }

    public function resetAddressForm(): void
    {
        $this->resetValidation();
        $this->reset(['address_id', 'city_id', 'street', 'house', 'building', 'apartment', 'entrance', 'floor', 'intercom', 'is_default']);
    }

    public function connectTelegram(TelegramService $telegramService): ?string
    {
        if (! $telegramService->isConfigured()) {
            $this->addError('telegram', __('Telegram-бот недоступен.'));

            return null;
        }

        return $telegramService->createConnectionUrl(auth()->user());
    }

    public function disconnectTelegram(TelegramService $telegramService): void
    {
        $telegramService->disconnect(auth()->user());
    }

    public function addAddress(): void
    {
        $this->resetAddressForm();
    }

    public function render(TelegramService $telegramService): View
    {
        $user = auth()->user();
        $addresses = $user->addresses()->with('city')->orderByDesc('is_default')->latest()->get();
        $cities = City::query()->where('is_active', true)->orderBy('name')->get();
        $telegramBotConfigured = $telegramService->isConfigured();

        return view('livewire.user-profile', compact('user', 'addresses', 'cities', 'telegramBotConfigured'));
    }
}
