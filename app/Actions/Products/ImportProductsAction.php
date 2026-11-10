<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Enums\Products\ProductUnitEnum;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Values\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

final class ImportProductsAction
{
    public function handle(string $path): int
    {
        return DB::transaction(function () use ($path): int {
            $changes = $this->read($path);
            foreach ($changes as $change) {
                $change['record']->save();
            }

            return $changes->count();
        });
    }

    public function preview(string $path): string
    {
        $changes = $this->read($path);

        return 'Будет обновлено записей: '.$changes->count()."\n\n".$changes->map(function (array $change): string {
            $record = $change['record'];
            $fields = collect($record->getDirty())->map(fn (mixed $value, string $field): string => ExportProductsAction::FIELD_LABELS[$field].': '.$this->display($field, $record->getRawOriginal($field)).' → '.$this->display($field, $value));

            return 'Строка '.$change['line'].' · '.($record instanceof Product ? 'Товар' : 'Вариация').' #'.$record->id."\n".$fields->implode("\n");
        })->implode("\n\n");
    }

    private function read(string $path): Collection
    {
        $reader = new Xlsx;
        $spreadsheet = null;
        try {
            $info = collect($reader->listWorksheetInfo($path))->firstWhere('worksheetName', 'Товары');
            if ($info === null || $info['totalRows'] > 20001) {
                throw ValidationException::withMessages(['file' => 'Нужен файл экспорта с листом «Товары», не более 20000 строк.']);
            }
            $spreadsheet = $reader->setLoadSheetsOnly('Товары')->load($path);
            $sheet = $spreadsheet->getSheetByName('Товары');
            $headers = $sheet->rangeToArray('A1:'.$sheet->getHighestDataColumn().'1', null, false, false)[0];
            $legacyHeaders = array_slice(ExportProductsAction::HEADERS, 0, 14);
            if ($headers !== ExportProductsAction::HEADERS && $headers !== $legacyHeaders) {
                throw ValidationException::withMessages(['file' => 'Заголовки или порядок колонок изменены. Скачайте новый экспорт.']);
            }
            $headerColumns = array_flip($headers);
            if ($sheet->getHighestDataRow() < 2) {
                throw ValidationException::withMessages(['file' => 'В файле нет товаров или вариаций.']);
            }
            foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
                if ($sheet->getCell($coordinate)->getDataType() === DataType::TYPE_FORMULA) {
                    throw ValidationException::withMessages(['file' => 'Ячейка '.$coordinate.': вместо формулы введите значение.']);
                }
            }
            $rows = $sheet->rangeToArray('A2:R'.$sheet->getHighestDataRow(), null, false, false);
        } catch (ReaderException) {
            throw ValidationException::withMessages(['file' => 'Не удалось прочитать файл XLSX.']);
        } finally {
            $spreadsheet?->disconnectWorksheets();
        }

        $productIds = collect($rows)->pluck(1)->filter(static fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false);
        $products = Product::query()->whereKey($productIds)->with(['variants', 'media'])->get()->keyBy('id');
        $changes = collect();
        $errors = [];
        $seen = [];
        $skus = [];
        $units = collect(ProductUnitEnum::cases())->mapWithKeys(static fn (ProductUnitEnum $unit): array => [$unit->getLabel() => $unit->value])->all();
        foreach ($rows as $index => $cells) {
            $line = $index + 2;
            if (count(array_filter($cells, static fn (mixed $value): bool => $value !== null && $value !== '')) === 0) {
                continue;
            }
            try {
                Validator::make(array_slice($cells, 0, 3), [
                    0 => 'required|in:Товар,Вариация', 1 => 'required|integer|min:1',
                    2 => 'nullable|required_if:0,Вариация|prohibited_if:0,Товар|integer|min:1',
                ], attributes: [0 => 'Тип строки', 1 => 'ID товара', 2 => 'ID вариации'])->validate();
                $isProduct = $cells[0] === 'Товар';
                $product = $products->get((int) $cells[1]);
                $record = $isProduct ? $product : $product?->variants->firstWhere('id', (int) $cells[2]);
                if ($record === null) {
                    throw ValidationException::withMessages(['file' => 'Товар или его вариация не найдены.']);
                }
                abort_unless(ProductResource::canEdit($product), 403);
                $key = $cells[0].':'.$record->id;
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages(['file' => 'Запись уже указана в строке '.$seen[$key].'.']);
                }
                $seen[$key] = $line;
                foreach ($isProduct ? range(7, 17) : range(3, 6) as $column) {
                    if (filled($cells[$column])) {
                        throw ValidationException::withMessages(['file' => 'Колонка «'.ExportProductsAction::HEADERS[$column].'» должна быть пустой.']);
                    }
                }
                $fields = $isProduct ? ['name', 'is_new', 'is_hit', 'is_active'] : ['sku', 'value', 'unit', 'price', 'old_price', 'is_active', 'is_preorder', 'weight', 'length', 'width', 'height'];
                $data = array_combine($fields, array_slice($cells, $isProduct ? 3 : 7, count($fields)));
                if (! $isProduct) {
                    foreach (['weight', 'length', 'width', 'height'] as $field) {
                        $label = ExportProductsAction::FIELD_LABELS[$field];
                        $data[$field] = isset($headerColumns[$label]) ? $cells[$headerColumns[$label]] : $record->{$field};
                    }
                }
                if (! $isProduct) {
                    $data['value'] = str_replace(',', '.', (string) $data['value']);
                    $data['price'] = str_replace(',', '.', (string) $data['price']);
                    $data['old_price'] = blank($data['old_price']) ? null : str_replace(',', '.', (string) $data['old_price']);
                }
                $rules = $isProduct ? ['name' => 'required|string|max:255', 'is_new' => 'required|in:Да,Нет', 'is_hit' => 'required|in:Да,Нет'] : [
                    'sku' => ['required', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($record->id)],
                    'value' => ['required', 'integer', 'min:1', 'max:999999999'],
                    'unit' => ['required', Rule::in(array_keys($units))],
                    'price' => ['required', 'numeric', 'min:1', 'regex:/^\d+(?:\.0{1,2})?$/'],
                    'old_price' => ['nullable', 'numeric', 'regex:/^\d+(?:\.0{1,2})?$/', 'gt:price'], 'is_preorder' => 'required|in:Да,Нет',
                    'weight' => 'required|integer|min:1', 'length' => 'required|integer|min:1',
                    'width' => 'required|integer|min:1', 'height' => 'required|integer|min:1',
                ];
                $rules['is_active'] = 'required|in:Да,Нет';
                Validator::make($data, $rules, attributes: ExportProductsAction::FIELD_LABELS)->validate();
                foreach (['is_new', 'is_hit', 'is_active', 'is_preorder'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $data[$field] = $data[$field] === 'Да';
                    }
                }
                if (! $isProduct) {
                    if (isset($skus[$data['sku']])) {
                        throw ValidationException::withMessages(['file' => 'Артикул повторяется в строке '.$skus[$data['sku']].'.']);
                    }
                    $skus[$data['sku']] = $line;
                    $data['unit'] = $units[$data['unit']];
                    foreach (['price', 'old_price'] as $field) {
                        if ($data[$field] === null) {
                            continue;
                        }
                        $data[$field] = Money::tryFromRubles($data[$field]);
                        if ($data[$field] === null) {
                            throw ValidationException::withMessages(['file' => ExportProductsAction::FIELD_LABELS[$field].' превышает допустимое значение.']);
                        }
                    }
                }
                foreach (['weight', 'length', 'width', 'height'] as $field) {
                    if (isset($data[$field])) {
                        $data[$field] = (int) $data[$field];
                    }
                }
                $record->fill($data);
                if ($record->isDirty()) {
                    $changes->push(['line' => $line, 'record' => $record]);
                }
            } catch (ValidationException $exception) {
                foreach ($exception->validator->errors()->all() as $message) {
                    $errors[] = 'Строка '.$line.': '.$message;
                }
            }
        }
        foreach ($products as $product) {
            if ($product->is_active && ($product->isDirty() || $product->variants->contains(static fn (ProductVariant $variant): bool => $variant->isDirty()))) {
                if (! $product->hasMedia('image') || ! $product->variants->contains('is_active', true)) {
                    $errors[] = 'Товар #'.$product->id.': для публикации нужны главное изображение и хотя бы одна активная вариация.';
                }
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => $errors]);
        }
        if ($seen === []) {
            throw ValidationException::withMessages(['file' => 'В файле нет товаров или вариаций.']);
        }

        return $changes;
    }

    private function display(string $field, mixed $value): string
    {
        return match (true) {
            $value === null => '—',
            in_array($field, ['is_new', 'is_hit', 'is_active', 'is_preorder'], true) => $value ? 'Да' : 'Нет',
            in_array($field, ['price', 'old_price'], true) => Money::fromMinor((int) $value)->format(),
            $field === 'unit' => ProductUnitEnum::from($value)->getLabel(),
            $field === 'value' => (string) (int) $value,
            default => (string) $value,
        };
    }
}
