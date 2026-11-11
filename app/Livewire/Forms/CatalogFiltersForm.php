<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\DTO\Products\CatalogFiltersDto;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class CatalogFiltersForm extends Form
{
    public string $priceFrom = '';

    public string $priceTo = '';

    public array $availability = [];

    public array $packageValues = [];

    public function validateFilters(array $packageOptions): ?array
    {
        $values = $this->validate($this->rules($packageOptions));

        if ($this->priceFrom !== '' && $this->priceTo !== '' && (int) $this->priceFrom > (int) $this->priceTo) {
            $this->addError('priceTo', __('Максимальная цена должна быть не меньше минимальной.'));

            return null;
        }

        return $values;
    }

    protected function rules(array $packageOptions = []): array
    {
        return [
            'priceFrom' => ['nullable', 'integer', 'min:0', 'max:'.CatalogFiltersDto::MAX_PRICE],
            'priceTo' => ['nullable', 'integer', 'min:0', 'max:'.CatalogFiltersDto::MAX_PRICE],
            'availability' => ['array', 'max:2'],
            'availability.*' => [Rule::in(CatalogFiltersDto::AVAILABILITY)],
            'packageValues' => ['array'],
            'packageValues.*' => ['string', Rule::in(array_column($packageOptions, 'value'))],
        ];
    }
}
