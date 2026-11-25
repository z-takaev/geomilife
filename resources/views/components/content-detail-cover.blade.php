@props(['date', 'image', 'title', 'expires' => false])

<header class="content-detail-heading">
    <time class="content-detail-date" datetime="{{ $date->toDateString() }}">
        <span class="icon icon-CalendarBlank" aria-hidden="true"></span>
        <span>{{ $expires ? __('Акция действует до :date', ['date' => $date->translatedFormat('j F Y')]) : $date->translatedFormat('j F Y') }}</span>
    </time>

    <div class="content-detail-main-image">
        <img loading="lazy" src="{{ $image }}" alt="{{ $title }}">
    </div>
</header>
