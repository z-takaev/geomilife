{{-- prettier-ignore-start --}}
<x-mail::message>
<h1>{{ __('Новый заказ №:number', ['number' => $order->number]) }}</h1>

<p>{{ __('Покупатель: :name', ['name' => $order->customer_name]) }}</p>
<p>{{ __('Телефон: :phone', ['phone' => $order->customer_phone]) }}</p>
<p>{{ __('Email: :email', ['email' => $order->customer_email]) }}</p>

<p>{{ __('Получение: :method', ['method' => $order->delivery_method->getLabel()]) }}</p>
<p>{{ __('Оплата: :method', ['method' => $order->payment_method->getLabel()]) }}</p>
<p><strong>{{ __('Сумма: :total', ['total' => $order->total->format()]) }}</strong></p>

<x-mail::button :url="$actionUrl">{{ __('Открыть заказ') }}</x-mail::button>

<x-slot:subcopy>
{{ __('Если кнопка не открывается, перейдите по ссылке:') }}
<span class="break-all">[{{ $actionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
</x-mail::message>
{{-- prettier-ignore-end --}}
