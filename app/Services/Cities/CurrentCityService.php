<?php

declare(strict_types=1);

namespace App\Services\Cities;

use App\Models\City;
use Illuminate\Support\Facades\Cookie;

final class CurrentCityService
{
    private array $cities = [];

    public function current(): ?City
    {
        $cityId = auth()->user()?->city_id ?? session('city.id') ?? request()->cookie('city_id');

        if ($cityId === null) {
            return null;
        }

        $cityId = (int) $cityId;

        if (! array_key_exists($cityId, $this->cities)) {
            $this->cities[$cityId] = City::query()->where('is_active', true)->find($cityId);
        }

        return $this->cities[$cityId];
    }

    public function select(int $cityId): City
    {
        $city = City::query()->where('is_active', true)->findOrFail($cityId);
        $user = auth()->user();

        if ($user !== null) {
            $user->city()->associate($city);
            $user->save();
        }

        session()->put('city', ['id' => $city->getKey()]);
        Cookie::queue(Cookie::forever('city_id', (string) $city->getKey()));

        $this->cities[$city->getKey()] = $city;

        return $city;
    }
}
