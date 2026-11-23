<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <title>{{ __('Сборка заказа №:number', ['number' => $order->number]) }}</title>
    <style>
        @page {
            margin: 28px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
        }

        h1 {
            font-size: 18px;
        }

        h2 {
            font-size: 13px;
            margin-top: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 12px 0;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #999;
            padding: 5px;
            text-align: left;
            overflow-wrap: break-word;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .notice {
            border: 2px solid #222;
            padding: 8px;
            font-weight: bold;
        }

        .totals {
            margin: 16px 0;
            border: 2px solid #222;
            padding: 8px 12px;
        }

        .totals__row {
            display: table;
            width: 100%;
            padding: 3px 0;
        }

        .totals__label,
        .totals__value {
            display: table-cell;
        }

        .totals__value {
            text-align: right;
        }

        .totals__total {
            border-top: 1px solid #999;
            font-size: 13px;
            font-weight: bold;
            padding-top: 7px;
            margin-top: 4px;
        }

        .package {
            border: 1px solid #777;
            padding: 10px;
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .package h3 {
            font-size: 12px;
            margin: 0 0 5px;
        }

        .package table {
            margin-bottom: 0;
        }

        .details {
            white-space: pre-wrap;
        }

        .signatures {
            margin-top: 25px;
        }
    </style>
</head>

<body>
    <h1>{{ __('Сборка заказа №:number', ['number' => $order->number]) }}</h1>
    <p>{{ __('Дата оформления') }}: {{ $order->created_at->format('d.m.Y H:i') }}</p>
    <p>{{ __('Получатель') }}: {{ $order->customer_name }} · {{ phone_format($order->customer_phone) }}</p>
    <p>{{ __('Способ получения') }}: {{ $deliveryLabel }}</p>
    <p>{{ __('Оплата') }}: {{ $order->payment_status->getLabel() }}</p>
    @if ($order->is_preorder)
        <p class="notice">{{ __('Предзаказ. Точные сроки согласовываются с менеджером.') }}</p>
    @endif
    @if ($order->payment_method->isPickup() && !$order->payment_status->isPaid())
        <p class="notice">{{ __('Получить оплату при выдаче') }}: {{ $order->total->format() }}</p>
    @endif
    <h2>{{ __('Адрес / пункт получения') }}</h2>
    <div class="details">{{ $address }}</div>
    @if ($order->delivery_method->isCourier())
        <p>{{ __('Доставка до двери') }}:
            {{ $order->delivery_details['delivery_to_door'] ?? false ? __('Да') : __('Нет') }}</p>
    @endif
    @if ($order->shipment?->cdek_number)
        <p>{{ __('Трек-номер СДЭК') }}: {{ $order->shipment->cdek_number }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>{{ __('Артикул') }}</th>
                <th>{{ __('Товар / фасовка') }}</th>
                <th>{{ __('Количество') }}</th>
                <th>{{ __('Цена') }}</th>
                <th>{{ __('Сумма') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->product_name }}<br>{{ $item->variant_name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->price->format() }}</td>
                    <td>{{ $item->total->format() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="totals">
        <div class="totals__row">
            <span class="totals__label">{{ __('Товары') }}</span>
            <span class="totals__value">{{ $order->subtotal->format() }}</span>
        </div>
        <div class="totals__row">
            <span class="totals__label">{{ __('Доставка') }}</span>
            <span class="totals__value">{{ $order->delivery_price->format() }}</span>
        </div>
        <div class="totals__row totals__total">
            <span class="totals__label">{{ __('Итого') }}</span>
            <span class="totals__value">{{ $order->total->format() }}</span>
        </div>
    </div>
    @if ($order->comment)
        <p>{{ __('Комментарий покупателя') }}: {{ $order->comment }}</p>
    @endif
    @if ($packages)
        <h2>{{ __('Распределение по коробкам') }}</h2>
        @foreach ($packages as $packageIndex => $package)
            <section class="package">
                <h3>{{ __('Коробка :current из :total', ['current' => $packageIndex + 1, 'total' => count($packages)]) }}
                </h3>
                <p>{{ __('Место №:number', ['number' => $package['number']]) }} ·
                    {{ $package['box_name'] ?? __('Коробка') }} · {{ $package['length'] }} × {{ $package['width'] }}
                    ×
                    {{ $package['height'] }} {{ __('мм') }} · {{ $package['weight'] }} {{ __('г') }}</p>
                <strong>{{ __('Содержимое коробки') }}</strong>
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('Артикул') }}</th>
                            <th>{{ __('Товар / фасовка') }}</th>
                            <th>{{ __('Количество') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($package['contents'] as $packedItem)
                            <tr>
                                <td>{{ $packedItem['sku'] }}</td>
                                <td>{{ $packedItem['label'] }}</td>
                                <td>{{ $packedItem['quantity'] }} {{ __('шт.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endforeach
    @endif
    <p class="signatures">{{ __('Собрал') }}: ____________________ {{ __('Проверил') }}: ____________________</p>
    <p>{{ __('Дата сборки') }}: ____________________</p>
</body>

</html>
