<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\City;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class RegistrationCitySelector extends Component
{
    public string $query = '';

    public ?int $selected_city_id = null;

    public function mount(?int $selectedCityId = null): void
    {
        $city = City::query()->where('is_active', true)->find($selectedCityId);

        $this->selected_city_id = $city?->id;
        $this->query = $city?->optionLabel() ?? '';
    }

    public function updatedQuery(): void
    {
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

    public function render(): View
    {
        $search = mb_substr(trim($this->query), 0, 255);
        $cities = mb_strlen($search) < 3
            ? collect()
            : City::query()
                ->where('is_active', true)
                ->searchByName($search)
                ->orderByDesc('is_suggested')
                ->orderBy('name')
                ->orderBy('region')
                ->orderBy('id')
                ->limit((int) config('pagination.per_page.city_search'))
                ->get();

        return view('livewire.registration-city-selector', compact('cities'));
    }
}
