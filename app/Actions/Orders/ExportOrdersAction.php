<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use App\ViewModel\Orders\OrderViewModel;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportOrdersAction
{
    public const array HEADERS = [
        'Номер заказа', 'Дата и время', 'Получатель', 'Телефон', 'Email', 'Город',
        'Способ получения', 'Адрес / ПВЗ', 'Статус заказа', 'Статус оплаты / возврата',
        'Способ оплаты', 'Сумма товаров, ₽', 'Доставка, ₽', 'Итого, ₽', 'Трек-номер СДЭК',
        'Предзаказ', 'Комментарий покупателя',
    ];

    public function handle(Builder $query): StreamedResponse
    {
        abort_unless(auth('filament')->check(), 403);

        return response()->streamDownload(function () use ($query): void {
            $spreadsheet = new Spreadsheet;

            try {
                $sheet = $spreadsheet->getActiveSheet()->setTitle('Заказы');
                $sheet->fromArray(self::HEADERS);
                $rowNumber = 2;

                foreach ((clone $query)->with('shipment')->lazy(200) as $order) {
                    foreach ($this->row($order) as $index => $value) {
                        $sheet->getCell([$index + 1, $rowNumber])->setValueExplicit(
                            $value, is_int($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING,
                        );
                    }

                    $rowNumber++;
                }

                $last = max(1, $rowNumber - 1);
                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:Q'.$last);
                $sheet->getStyle('A1:Q1')->getFont()->setBold(true);
                $sheet->getStyle('A1:Q'.$last)->getAlignment()->setWrapText(true)->setVertical('top');

                if ($last >= 2) {
                    $sheet->getStyle('L2:N'.$last)->getNumberFormat()->setFormatCode('0');
                }

                foreach (range('A', 'Q') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(in_array($column, ['H', 'Q'], true) ? 45 : 24);
                }
                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, 'orders-'.now()->format('Y-m-d-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function row(Order $order): array
    {
        return [
            (string) $order->number, $order->created_at->format('d.m.Y H:i:s'),
            $order->customer_name, $order->customer_phone, $order->customer_email,
            (string) ($order->delivery_address['city'] ?? ''), OrderViewModel::deliveryLabel($order),
            OrderViewModel::address($order), $order->status->getLabel(), $order->payment_status->getLabel(),
            $order->payment_method->getLabel(),
            intdiv($order->subtotal->minorAmount(), 100), intdiv($order->delivery_price->minorAmount(), 100),
            intdiv($order->total->minorAmount(), 100), (string) ($order->shipment?->cdek_number ?? ''),
            $order->is_preorder ? __('Да') : __('Нет'), (string) $order->comment,
        ];
    }
}
