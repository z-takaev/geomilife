<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Enums\ProductUnit;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportProductsAction
{
    public const array HEADERS = [
        'Тип строки', 'ID товара', 'ID вариации', 'Название', 'Новинка', 'Хит', 'Товар активен',
        'Артикул', 'Количество (фасовка)', 'Единица', 'Цена, ₽', 'Скидка, %',
        'Вариация активна', 'Доступен по предзаказу',
    ];

    public const array FIELD_LABELS = [
        'name' => 'Название', 'is_new' => 'Новинка', 'is_hit' => 'Хит', 'is_active' => 'Активен',
        'sku' => 'Артикул', 'value' => 'Количество (фасовка)', 'unit' => 'Единица',
        'price' => 'Цена, ₽', 'discount_percent' => 'Скидка, %', 'is_preorder' => 'Доступен по предзаказу',
    ];

    public function handle(Builder $query): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            $spreadsheet = new Spreadsheet;
            try {
                $sheet = $spreadsheet->getActiveSheet()->setTitle('Товары');
                $sheet->fromArray(self::HEADERS);
                $line = 2;
                foreach ((clone $query)->with('variants')->lazy(200) as $product) {
                    $this->writeRow($sheet, $line++, $product);
                    foreach ($product->variants as $variant) {
                        $this->writeRow($sheet, $line++, $variant);
                    }
                }
                $sheet->freezePane('D2');
                $sheet->getStyle('A1:N'.max(1, $line - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A1:N1')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['wrapText' => true],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(42);
                foreach (range('A', 'N') as $column) {
                    $sheet->getColumnDimension($column)->setWidth($column === 'D' ? 45 : 20);
                }
                $sheet->getProtection()->setSheet(true);

                $instructions = $spreadsheet->createSheet()->setTitle('Как заполнить');
                $instructions->fromArray([
                    ['Поле', 'Подсказка'],
                    ['Редактирование', 'ID и тип строки менять нельзя. Заполняйте только поля соответствующего типа строки.'],
                    ['Товар / Вариация', 'Общие поля редактируются один раз в строке товара. Остальные — в строках вариаций.'],
                    ['Да / Нет', 'Да — включено (1), Нет — выключено (0). Выбирайте значение из списка.'],
                    ['Количество', 'Фасовка, не складской остаток. Целое число от 1.'],
                    ['Цена / Скидка', 'Цена — целое число рублей от 1. Скидка — 1–99%. Пустая ячейка или 0 убирает скидку.'],
                    ['Артикул / Единица', 'Артикул должен быть уникальным. Единицу выбирайте из списка.'],
                    ['Импорт', 'Перед правкой скачайте актуальный экспорт. Строки обновляются по ID; отсутствующие строки ничего не удаляют.'],
                ]);
                $instructions->getColumnDimension('A')->setWidth(28);
                $instructions->getColumnDimension('B')->setWidth(110);
                $instructions->getStyle('A1:B8')->getAlignment()->setWrapText(true);
                $instructions->getStyle('A1:B8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                foreach (range(1, 8) as $row) {
                    $instructions->getRowDimension($row)->setRowHeight(32);
                }
                $spreadsheet->setActiveSheetIndex(0);
                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, 'products-'.now()->format('Y-m-d-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function writeRow(Worksheet $sheet, int $line, Product|ProductVariant $record): void
    {
        $isProduct = $record instanceof Product;
        $yesNo = static fn (bool $value): string => $value ? 'Да' : 'Нет';
        $values = $isProduct
            ? ['Товар', (string) $record->id, null, $record->name, $yesNo($record->is_new), $yesNo($record->is_hit), $yesNo($record->is_active)]
            : ['Вариация', (string) $record->product_id, (string) $record->id, null, null, null, null,
                $record->sku, (float) $record->value, $record->unit->getLabel(),
                (float) $record->price->toRubles(), $record->discount_percent ?: null,
                $yesNo($record->is_active), $yesNo($record->is_preorder)];
        foreach ($values as $index => $value) {
            if ($value !== null) {
                $sheet->getCell([$index + 1, $line])->setValueExplicit($value, is_string($value) ? DataType::TYPE_STRING : DataType::TYPE_NUMERIC);
            }
        }
        $editable = $isProduct ? 'D'.$line.':G'.$line : 'H'.$line.':N'.$line;
        $sheet->getStyle($editable)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
        $sheet->getStyle('H'.$line)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('I'.$line)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
        $sheet->getStyle('K'.$line.':L'.$line)->getNumberFormat()->setFormatCode('0');
        if ($isProduct) {
            $sheet->getStyle('A'.$line.':N'.$line)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8F5F2');
            $sheet->getStyle('A'.$line.':N'.$line)->getFont()->setBold(true);
        }
        foreach ($isProduct ? ['E', 'F', 'G'] : ['M', 'N', 'J'] as $column) {
            $options = $column === 'J'
                ? implode(',', array_map(static fn (ProductUnit $unit): string => $unit->getLabel(), ProductUnit::cases()))
                : 'Да,Нет';
            $sheet->getCell($column.$line)->getDataValidation()
                ->setType(DataValidation::TYPE_LIST)->setFormula1('"'.$options.'"')
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)->setErrorStyle(DataValidation::STYLE_STOP)->setError('Выберите значение из списка.');
        }
    }
}
