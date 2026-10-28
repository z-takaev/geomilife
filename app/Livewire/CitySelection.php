<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\City;
use App\Services\Cities\CurrentCityService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

final class CitySelection extends Component
{
    private const int SEARCH_LIMIT = 5;

    public string $query = '';

    public function select(int $cityId, CurrentCityService $currentCity): void
    {
        $city = $currentCity->select($cityId);

        $this->reset('query');
        $this->dispatch('city-selected', id: $city->getKey(), name: $city->name);
    }

    public function render(): View
    {
        $query = mb_substr(trim($this->query), 0, 255);
        $searchResults = collect();

        if ($query !== '') {
            $searchResults = City::query()
                ->where('is_active', true)
                ->whereLike('name', '%'.addcslashes($query, '\\%_').'%')
                ->orderByDesc('is_suggested')
                ->orderBy('name')
                ->orderBy('region')
                ->orderBy('id')
                ->limit(self::SEARCH_LIMIT)
                ->get();
        }

        $cities = City::query()
            ->where('is_active', true)
            ->where(static fn (Builder $query): Builder => $query
                ->where('is_suggested', true)
                ->orWhere('is_pickup_available', true)
                ->orWhere('is_courier_available', true))
            ->orderBy('name')
            ->get();

        [$featuredCities, $suggestedCities] = $cities->partition(
            static fn (City $city): bool => $city->is_pickup_available || $city->is_courier_available,
        );

        $cityColumns = $suggestedCities
            ->groupBy(static fn (City $city): string => mb_strtoupper(mb_substr($city->name, 0, 1)))
            ->splitIn(3);

        return view('livewire.city-selection', compact('query', 'searchResults', 'featuredCities', 'cityColumns'));
    }
}
