<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Cities\GetCitySuggestionsAction;
use App\Models\City;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class RegistrationCitySelector extends Component
{
    public string $query = '';

    public ?int $selected_city_id = null;

    public function mount(?int $selectedCityId = null): void
    {
        if ($selectedCityId === null) {
            return;
        }

        $city = City::query()->where('is_active', true)->find($selectedCityId);

        $this->selected_city_id = $city?->id;
        $this->query = $city?->optionLabel() ?? '';
    }

    public function updatedQuery(): void
    {
        $this->validateOnly('query');

        if ($this->selected_city_id === null) {
            return;
        }

        $selectedCity = City::query()->whereKey($this->selected_city_id)->first();

        if ($selectedCity === null || $this->query !== $selectedCity->optionLabel()) {
            $this->selected_city_id = null;
        }
    }

    public function select(int $cityId): void
    {
        $city = City::query()->where('is_active', true)->findOrFail($cityId);

        $this->selected_city_id = $city->id;
        $this->query = $city->optionLabel();
    }

    public function render(GetCitySuggestionsAction $getCitySuggestions): View
    {
        $cities = $getCitySuggestions->run($this->query);

        return view('livewire.registration-city-selector', compact('cities'));
    }

    protected function rules(): array
    {
        return [
            'query' => ['string', 'max:255'],
        ];
    }
}
