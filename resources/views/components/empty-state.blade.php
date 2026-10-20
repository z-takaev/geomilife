@props(['title', 'description' => null])

<div {{ $attributes->class(['empty-state']) }}>
    <span class="icon icon-search" aria-hidden="true"></span>
    <h2>{{ $title }}</h2>

    @if (filled($description))
        <p>{{ $description }}</p>
    @endif
</div>
