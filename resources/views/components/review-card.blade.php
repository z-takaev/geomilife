@props(['review', 'expandable' => false])

<article @class([
    'home-review-card wow fadeInUp',
    'home-review-card--expandable' => $expandable,
]) @if ($expandable) data-review-card-expandable @endif>
    <header class="home-review-meta">
        @if ($review->source !== null)
            <img class="home-review-source-logo" src="{{ asset($review->source->logoPath()) }}"
                alt="{{ __($review->source->getLabel()) }}">
        @endif
        <span class="home-review-rating" aria-label="{{ __('Рейтинг: :rating из 5', ['rating' => $review->rating]) }}">
            <span class="icon icon-star-4" aria-hidden="true"></span>
            <span>{{ $review->rating }}</span>
        </span>
    </header>
    @if ($expandable)
        <div class="home-review-content">
            <p class="home-review-text" data-review-text>{{ $review->text }}</p>
            <button class="home-review-toggle" type="button" data-review-toggle
                data-show-label="{{ __('Показать полностью') }}" data-hide-label="{{ __('Свернуть') }}"
                aria-expanded="false" hidden>
                {{ __('Показать полностью') }}
            </button>
        </div>
    @else
        <p class="home-review-text">{{ $review->text }}</p>
    @endif
    <footer class="home-review-footer">
        <p class="home-review-author">{{ $review->author_name }}</p>
        <time class="home-review-date" datetime="{{ $review->reviewed_at->toDateString() }}">
            {{ $review->reviewed_at->translatedFormat('j F Y') }}
        </time>
    </footer>
</article>
