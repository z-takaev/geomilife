@props([
    'id',
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'errorBag',
    'required' => false,
    'requiredMarker' => true,
])

@php
    $errorBagInstance = $errors->getBag($errorBag);
    $hasError = $errorBagInstance->has($name);
@endphp

<fieldset class="tf-field">
    <label for="{{ $id }}" class="tf-lable">
        {{ $label }}
        @if ($required && $requiredMarker)
            <span class="text-secondary">*</span>
        @endif
    </label>
    <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
        @required($required) {{ $attributes->class(['is-invalid' => $hasError]) }}>
    @if ($hasError)
        <span class="invalid-feedback d-block">{{ $errorBagInstance->first($name) }}</span>
    @endif
</fieldset>
