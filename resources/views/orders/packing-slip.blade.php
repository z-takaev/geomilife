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
    <p>{{ __('Получатель') }}: {{ $order->customer_name }} · {{ $order->customer_phone }}</p>
    <p>{{ __('Способ получения') }}: {{ $deliveryLabel }}</p>
    <p>{{ __('Статус заказа') }}: {{ $order->status->getLabel() }} · {{ __('Оплата') }}:
        {{ $order->payment_status->getLabel() }}</p>
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
    <p>{{ __('Товары') }}: {{ $order->subtotal->format() }} · {{ __('Доставка') }}:
        {{ $order->delivery_price->format() }} · {{ __('Итого') }}: {{ $order->total->format() }}</p>
    @if ($order->comment)
        <p>{{ __('Комментарий покупателя') }}: {{ $order->comment }}</p>
    @endif
    @if ($packages)
        <h2>{{ __('Распределение по коробкам') }}</h2>
        @foreach ($packages as $package)
            <p><strong>{{ __('Место №:number', ['number' => $package['number']]) }}</strong> ·
                {{ $package['box_name'] ?? __('Коробка') }} · {{ $package['length'] }} × {{ $package['width'] }} ×
                {{ $package['height'] }} {{ __('мм') }} · {{ $package['weight'] }} {{ __('г') }}</p>
            @foreach ($package['contents'] as $packedItem)
                <p>{{ $packedItem['sku'] }} · {{ $packedItem['label'] }} · {{ $packedItem['quantity'] }}
                    {{ __('шт.') }}</p>
            @endforeach
        @endforeach
    @endif
    <p class="signatures">{{ __('Собрал') }}: ____________________ {{ __('Проверил') }}: ____________________</p>
    <p>{{ __('Дата сборки') }}: ____________________</p>
</body>

</html>
