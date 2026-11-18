<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Cities\GetCitySuggestionsAction;
use App\Models\User;
use App\Livewire\Forms\UserProfileForm;
use App\Models\City;
use App\Services\Cities\CurrentCityService;
use App\Services\Customers\SavedDeliveryLocationService;
use App\Services\Telegram\TelegramConnectionService;
use App\Support\Phone\PhoneFormat;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class UserProfile extends Component
{
    public UserProfileForm $form;

    public string $city_search = '';

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->first_name = $user->first_name;
        $this->form->last_name = $user->last_name ?? '';
        $this->form->birth_date = $user->birth_date?->format('Y-m-d');
        $this->form->phone = PhoneFormat::format((string) $user->phone);
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
        /** @var User */
        $user = Auth::user();

        // TODO: Избавиться от нужны использовать PhoneFormat
        $this->form->phone = PhoneFormat::normalize((string) $this->form->phone);
        $validated = $this->form->validate();

        $user->fill($validated);
        $user->save();

        $this->form->phone = PhoneFormat::format((string) $user->phone);

        app(CurrentCityService::class)->select($validated['city_id']);

        $this->dispatch('notify', message: __('Данные профиля сохранены.'), type: 'success');
    }

    public function setDefaultAddress(int $addressId): void
    {
        app(SavedDeliveryLocationService::class)
            ->setDefaultAddress(Auth::user(), $addressId);

        $this->dispatch('notify', message: __('Активный адрес обновлен.'), type: 'success');
    }

    public function deleteAddress(int $addressId): void
    {
        app(SavedDeliveryLocationService::class)
            ->deleteAddress(Auth::user(), $addressId);
    }

    public function setDefaultDeliveryPoint(int $pointId): void
    {
        app(SavedDeliveryLocationService::class)
            ->setDefaultDeliveryPoint(Auth::user(), $pointId);

        $this->dispatch('notify', message: __('Активный пункт обновлён.'), type: 'success');
    }

    public function deleteDeliveryPoint(int $pointId): void
    {
        app(SavedDeliveryLocationService::class)
            ->deleteDeliveryPoint(Auth::user(), $pointId);
    }

    public function connectTelegram(TelegramConnectionService $telegramService): ?string
    {
        if (! $telegramService->isConfigured()) {
            $this->addError('telegram', __('Telegram-бот недоступен.'));

            return null;
        }

        return $telegramService->createConnectionUrl(Auth::user());
    }

    public function disconnectTelegram(TelegramConnectionService $telegramService): void
    {
        $telegramService->disconnect(Auth::user());
    }

    public function render(TelegramConnectionService $telegramService, GetCitySuggestionsAction $getCitySuggestions): View
    {
        /** @var User */
        $user = Auth::user();
        $addresses = $user->addresses()->with('city')->orderByDesc('is_default')->latest()->get();
        $deliveryPoints = $user->savedDeliveryPoints()->with('city')->orderByDesc('is_default')->latest()->get();
        $telegramBotConfigured = $telegramService->isConfigured(); // TODO: Вынести в хелпер и в шаблоне использовать его а не явно передавать
        // TODO: Заменить название экшена
        $cities = $getCitySuggestions->run($this->city_search);

        return view('livewire.user-profile', compact('user', 'addresses', 'deliveryPoints', 'telegramBotConfigured', 'cities'));
    }
}
