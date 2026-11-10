<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Customers\SavedDeliveryLocationService;
use App\Services\Telegram\TelegramConnectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class UserProfile extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public ?string $birth_date = null;

    public ?string $phone = null;

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

        return view('livewire.user-profile', compact('user', 'addresses', 'deliveryPoints', 'telegramBotConfigured'));
    }
}
