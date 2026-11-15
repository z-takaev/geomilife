@props(['title', 'description' => null, 'icon' => 'icon-package'])

<div {{ $attributes->class(['empty-state']) }}>
    <span class="icon {{ $icon }}" aria-hidden="true"></span>
    <h2>{{ $title }}</h2>

    @if (filled($description))
        <p>{{ $description }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="empty-state__actions">
            {{ $slot }}
        </div>
    @endif
</div>
