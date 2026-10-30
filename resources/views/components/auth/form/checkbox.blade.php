@props(['id', 'name', 'value' => '1', 'errorBag', 'checked' => false, 'required' => false])

@php
    $errorBagInstance = $errors->getBag($errorBag);
    $hasError = $errorBagInstance->has($name);
@endphp

<div class="checkbox-wrap">
    <input id="{{ $id }}" name="{{ $name }}" value="{{ $value }}" type="checkbox"
        @checked($checked) @required($required) {{ $attributes->class(['tf-check', 'style-4', 'radius-3']) }}>
    <label for="{{ $id }}" class="text-black text-caption-01">
        {{ $slot }}
    </label>
</div>
@if ($hasError)
    <span class="invalid-feedback d-block">{{ $errorBagInstance->first($name) }}</span>
@endif
