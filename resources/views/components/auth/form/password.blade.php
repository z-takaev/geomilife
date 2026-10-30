@props(['id', 'name', 'label', 'errorBag', 'required' => false])

@php
    $errorBagInstance = $errors->getBag($errorBag);
    $hasError = $errorBagInstance->has($name);
@endphp

<fieldset class="tf-field">
    <label for="{{ $id }}" class="tf-lable">
        {{ $label }}
        @if ($required)
            <span class="text-secondary">*</span>
        @endif
    </label>
    <div class="password-wrapper">
        <input type="password" id="{{ $id }}" name="{{ $name }}" @required($required)
            {{ $attributes->class(['password-field', 'is-invalid' => $hasError]) }}>
        <span class="toggle-pass icon-eye-open"></span>
    </div>
    @if ($hasError)
        <span class="invalid-feedback d-block">{{ $errorBagInstance->first($name) }}</span>
    @endif
</fieldset>
