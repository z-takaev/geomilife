@props(['name', 'label', 'required' => false, 'containerClass' => ''])

<fieldset @class(['checkout-field', $containerClass])>
    <label for="checkout-{{ $name }}">
        {{ $label }}
        @if ($required)
            <span class="checkout-field__required" aria-hidden="true"></span>
        @endif
    </label>
    <input id="checkout-{{ $name }}" {{ $attributes->merge(['type' => 'text']) }} @required($required)>
    {{ $slot }}
    @error('form.' . $name)
        <small class="checkout-field-error">{{ $message }}</small>
    @enderror
</fieldset>
