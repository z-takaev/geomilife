@props(['variants', 'selectedId', 'compact' => false])

<div @class([
    'product-card-weights' => $compact,
    'variant-picker-values' => !$compact,
]) role="group" aria-label="{{ __('Выберите вариацию') }}">
    @foreach ($variants as $variant)
        <button type="button" @class([
            'product-card-weight' => $compact,
            'unit-btn' => !$compact,
            'is-active' => $compact && $variant['id'] === $selectedId,
            'active' => !$compact && $variant['id'] === $selectedId,
        ]) x-on:click="selectVariant({{ $variant['id'] }})"
            x-bind:class="{ '{{ $compact ? 'is-active' : 'active' }}': selectedVariantId === {{ $variant['id'] }} }"
            x-bind:aria-pressed="selectedVariantId === {{ $variant['id'] }}"
            aria-pressed="{{ $variant['id'] === $selectedId ? 'true' : 'false' }}">
            {{ $variant['label'] }}
        </button>
    @endforeach
</div>
