{{-- prettier-ignore-start --}}
<x-mail::message>
<h1>{{ __('Ваш заказ принят') }}</h1>
<p>{{ __('Здравствуйте, :name!', ['name' => $order->customer_name]) }}</p>
<p><strong>{{ __('Заказ №:number', ['number' => $order->number]) }}</strong><br>
{{ __('Дата оформления: :date', ['date' => $presentation['createdAtLabel']]) }}</p>

@if ($order->is_preorder)
<p><strong>{{ __('Предзаказ') }}</strong><br>{{ $presentation['deliveryHint'] }}</p>
@endif

<h2>{{ __('Товары в заказе') }}</h2>
@foreach ($order->items as $item)
<p><strong>{{ $item->product_name }}</strong><br>
@if (filled($item->variant_name))
{{ $item->variant_name }}<br>
@endif
@if (filled($item->sku))
{{ __('Артикул: :sku', ['sku' => $item->sku]) }}<br>
@endif
{{ __(':quantity × :price', ['quantity' => $item->quantity, 'price' => $item->price->format()]) }}<br>
<strong>{{ __('Сумма: :total', ['total' => $item->total->format()]) }}</strong>
</p>
@endforeach

<x-mail::panel>
{{ __('Товары: :subtotal', ['subtotal' => ($order->subtotal ?? $order->total)->format()]) }}<br>
{{ __('Доставка: :price', ['price' => $presentation['deliveryPriceLabel']]) }}<br>
<strong>{{ __('Итого: :total', ['total' => $order->total->format()]) }}</strong>
</x-mail::panel>

<h2>{{ __('Оплата') }}</h2>
<p>{{ __('Способ оплаты: :method', ['method' => $presentation['paymentLabel']]) }}<br>
{{ __('Статус оплаты: :status', ['status' => $order->payment_status->getLabel()]) }}</p>
@if ($presentation['canPay'])
<p>{{ __('Оплатите до :time. После истечения срока неоплаченный заказ отменяется автоматически.', ['time' => $presentation['paymentDeadlineLabel']]) }}
</p>
@endif

<h2>{{ __('Получение заказа') }}</h2>
<p><strong>{{ $presentation['deliveryLabel'] }}</strong><br>{{ $presentation['addressLabel'] }}</p>
@if ($presentation['workingHours'])
<p>{{ __('График: :hours', ['hours' => $presentation['workingHours']]) }}</p>
@endif
@if ($order->delivery_method->isCdek() && filled($order->delivery_address['code'] ?? null))
<p>{{ __('Пункт: :code', ['code' => $order->delivery_address['code']]) }}</p>
@endif
@if (!$order->is_preorder)
<p>{{ $presentation['deliveryHint'] }}</p>
@endif

<h2>{{ __('Получатель') }}</h2>
<p>{{ $order->customer_name }}<br>
{{ __('Телефон: :phone', ['phone' => $order->customer_phone]) }}<br>
{{ __('Email: :email', ['email' => $order->customer_email]) }}</p>

@if (filled($order->comment))
<h2>{{ __('Комментарий к заказу') }}</h2>
<p>{{ $order->comment }}</p>
@endif

<h2>{{ __('Что дальше') }}</h2>
<p>{{ $presentation['nextStep'] }}</p>

<x-mail::button :url="$actionUrl">{{ __('Открыть заказ') }}</x-mail::button>

<p>{{ __('Спасибо за заказ в GeoMiLife!') }}</p>

<x-slot:subcopy>
{{ __('Если кнопка не открывается, перейдите по ссылке:') }}
<span class="break-all">[{{ $actionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
</x-mail::message>
{{-- prettier-ignore-end --}}
