@props(['date', 'image', 'title', 'expires' => false])

<header class="content-detail-heading">
    <time class="content-detail-date" datetime="{{ $date->toDateString() }}">
        <span class="icon icon-CalendarBlank" aria-hidden="true"></span>
        <span>{{ $expires ? __('Акция действует до :date', ['date' => $date->translatedFormat('j F Y')]) : $date->translatedFormat('j F Y') }}</span>
    </time>

    <h1 class="content-detail-title font-sora">{{ $title }}</h1>

    <div class="content-detail-main-image">
        <img loading="lazy" src="{{ $image }}" alt="{{ $title }}">
    </div>
</header>
