<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Cities\GetCitySuggestionsAction;
use App\Models\City;
use App\Services\Cities\CurrentCityService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

final class CitySelector extends Component
{
    public string $query = '';

    public bool $options_loaded = false;

    public function detect(CurrentCityService $currentCityService): void
    {
        $city = $currentCityService->getOrDetect();

        if ($city !== null) {
            $this->dispatch('city-detected', id: $city->getKey(), name: $city->name);
        }
    }

    public function select(int $cityId, CurrentCityService $currentCityService): void
    {
        $city = $currentCityService->select($cityId);

        $this->reset('query');
        $this->dispatch('city-selected', id: $city->getKey(), name: $city->name);
    }

    public function loadOptions(): void
    {
        $this->options_loaded = true;
    }

    public function render(GetCitySuggestionsAction $getCitySuggestions): View
    {
        $currentCity = app(CurrentCityService::class)->current();
        $shouldDetectCity = $currentCity === null && auth()->user()?->city_id === null;
        $searchResults = $getCitySuggestions->run($this->query);

        $cities = $this->options_loaded ? City::cached() : new Collection;

        [$featuredCities, $suggestedCities] = $cities->partition(
            static fn (City $city): bool => $city->is_pickup_available || $city->is_courier_available,
        );

        $cityColumns = $suggestedCities
            ->groupBy(static fn (City $city): string => mb_strtoupper(mb_substr($city->name, 0, 1)))
            ->splitIn(3);

        return view('livewire.city-selector', compact('currentCity', 'shouldDetectCity', 'searchResults', 'featuredCities', 'cityColumns'));
    }
}
