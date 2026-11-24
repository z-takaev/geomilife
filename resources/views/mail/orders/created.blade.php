{{-- prettier-ignore-start --}}
<x-mail::message>
<h1>{{ __('Ваш заказ принят') }}</h1>
<p>{{ __('Здравствуйте, :name!', ['name' => $order->customer_name]) }}</p>
<p><strong>{{ __('Заказ №:number', ['number' => $order->number]) }}</strong><br>
{{ __('Дата оформления: :date', ['date' => $viewModel->createdAtLabel]) }}</p>

@if ($order->is_preorder)
<p><strong>{{ __('Предзаказ') }}</strong><br>{{ $viewModel->deliveryHint }}</p>
@endif

<h2>{{ __('Товары в заказе') }}</h2>
@foreach ($order->items as $item)
<p><strong>{{ $item->product_name }}</strong><br>
@if (filled($item->variant_name))
{{ $item->variant_name }}<br>
@endif
{{ __(':quantity × :price', ['quantity' => $item->quantity, 'price' => $item->price->format()]) }}<br>
<strong>{{ __('Сумма: :total', ['total' => $item->total->format()]) }}</strong>
</p>
@endforeach

<x-mail::panel>
{{ __('Товары: :subtotal', ['subtotal' => ($order->subtotal ?? $order->total)->format()]) }}<br>
{{ __('Доставка: :price', ['price' => $viewModel->delivery->priceLabel]) }}<br>
<strong>{{ __('Итого: :total', ['total' => $order->total->format()]) }}</strong>
</x-mail::panel>

<h2>{{ __('Оплата') }}</h2>
<p>{{ __('Способ оплаты: :method', ['method' => $order->payment_method->getLabel()]) }}</p>

<h2>{{ __('Получение заказа') }}</h2>
<p><strong>{{ $viewModel->delivery->label }}</strong><br>{{ $viewModel->delivery->address }}</p>
@if ($viewModel->delivery->workingHours)
<p>{{ __('График: :hours', ['hours' => $viewModel->delivery->workingHours]) }}</p>
@endif
@if ($order->delivery_method->isCdek() && filled($order->delivery_address['code'] ?? null))
<p>{{ __('Пункт: :code', ['code' => $order->delivery_address['code']]) }}</p>
@endif
@if (!$order->is_preorder)
<p>{{ $viewModel->deliveryHint }}</p>
@endif

<h2>{{ __('Получатель') }}</h2>
<p>{{ $order->customer_name }}<br>
{{ __('Телефон: :phone', ['phone' => phone_format($order->customer_phone)]) }}<br>
{{ __('Email: :email', ['email' => $order->customer_email]) }}</p>

@if (filled($order->comment))
<h2>{{ __('Комментарий к заказу') }}</h2>
<p>{{ $order->comment }}</p>
@endif

@if ($actionUrl)
<x-mail::button :url="$actionUrl">{{ __('Открыть заказ') }}</x-mail::button>
@endif

<p>{{ __('Спасибо за заказ в GeoMiLife!') }}</p>

@if ($actionUrl)
<x-slot:subcopy>
{{ __('Если кнопка не открывается, перейдите по ссылке:') }}
<span class="break-all">[{{ $actionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endif
</x-mail::message>
{{-- prettier-ignore-end --}}
