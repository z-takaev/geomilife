<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Forms\UserProfileForm;
use App\Models\City;
use App\Services\Cities\CurrentCityService;
use App\Services\Customers\SavedDeliveryLocationService;
use App\Services\Telegram\TelegramConnectionService;
use App\Support\Phone\PhoneFormat;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class UserProfile extends Component
{
    public UserProfileForm $form;

    public string $city_search = '';

    public function mount(): void
    {
        $user = auth()->user();

        $this->form->first_name = $user->first_name;
        $this->form->last_name = $user->last_name ?? '';
        $this->form->birth_date = $user->birth_date?->format('Y-m-d');
        $this->form->phone = $user->phone;
        $this->form->city_id = $user->city_id;
        $this->city_search = $user->city?->optionLabel() ?? '';
    }

    public function updatedCitySearch(): void
    {
        if ($this->form->city_id === null) {
            return;
        }

        $city = City::query()->whereKey($this->form->city_id)->first();

        if ($city === null || $this->city_search !== $city->optionLabel()) {
            $this->form->city_id = null;
        }
    }

    public function selectCity(int $cityId): void
    {
        $city = City::query()->where('is_active', true)->findOrFail($cityId);

        $this->form->city_id = $city->id;
        $this->city_search = $city->optionLabel();
    }

    public function saveProfile(): void
    {
        $user = auth()->user();
        $this->form->phone = PhoneFormat::normalize((string) $this->form->phone);
        $validated = $this->form->validate();

        $user->fill($validated);
        $user->save();
        app(CurrentCityService::class)->select($validated['city_id']);
        $this->dispatch('notify', message: __('Данные профиля сохранены.'), type: 'success');
    }

    public function setDefaultAddress(int $addressId): void
    {
        app(SavedDeliveryLocationService::class)->setDefaultAddress(auth()->user(), $addressId);
        $this->dispatch('notify', message: __('Активный адрес обновлен.'), type: 'success');
    }

    public function deleteAddress(int $addressId): void
    {
        app(SavedDeliveryLocationService::class)->deleteAddress(auth()->user(), $addressId);
    }

    public function setDefaultDeliveryPoint(int $pointId): void
    {
        app(SavedDeliveryLocationService::class)->setDefaultDeliveryPoint(auth()->user(), $pointId);
        $this->dispatch('notify', message: __('Активный пункт обновлён.'), type: 'success');
    }

    public function deleteDeliveryPoint(int $pointId): void
    {
        app(SavedDeliveryLocationService::class)->deleteDeliveryPoint(auth()->user(), $pointId);
    }

    public function connectTelegram(TelegramConnectionService $telegramService): ?string
    {
        if (! $telegramService->isConfigured()) {
            $this->addError('telegram', __('Telegram-бот недоступен.'));

            return null;
        }

        return $telegramService->createConnectionUrl(auth()->user());
    }

    public function disconnectTelegram(TelegramConnectionService $telegramService): void
    {
        $telegramService->disconnect(auth()->user());
    }

    public function render(TelegramConnectionService $telegramService): View
    {
        $user = auth()->user();
        $addresses = $user->addresses()->with('city')->orderByDesc('is_default')->latest()->get();
        $deliveryPoints = $user->savedDeliveryPoints()->with('city')->orderByDesc('is_default')->latest()->get();
        $telegramBotConfigured = $telegramService->isConfigured();
        $citySearch = trim($this->city_search);
        $cities = mb_strlen($citySearch) < 3
            ? collect()
            : City::query()
                ->where('is_active', true)
                ->searchByName($citySearch)
                ->orderByDesc('is_suggested')
                ->orderBy('name')
                ->orderBy('region')
                ->orderBy('id')
                ->limit((int) config('pagination.per_page.city_search'))
                ->get();

        return view('livewire.user-profile', compact('user', 'addresses', 'deliveryPoints', 'telegramBotConfigured', 'cities'));
    }
}
