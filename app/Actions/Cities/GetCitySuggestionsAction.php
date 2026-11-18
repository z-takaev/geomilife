<?php

declare(strict_types=1);

namespace App\Actions\Cities;

use App\Models\City;
use Illuminate\Support\Collection;

// TODO: Вынести в метод модели
final class GetCitySuggestionsAction
{
    public function run(string $search): Collection
    {
        $search = mb_substr(trim($search), 0, 255);

        if (mb_strlen($search) < (int) config('cities.search_min_length')) {
            return collect();
        }

        return City::query()
            ->where('is_active', true)
            ->searchByName($search)
            ->orderByDesc('is_suggested')
            ->orderBy('name')
            ->orderBy('region')
            ->orderBy('id')
            ->limit((int) config('pagination.per_page.city_search'))
            ->get();
    }
}
