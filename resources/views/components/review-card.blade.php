@props(['review', 'expandable' => false])

<article @class([
    'home-review-card wow fadeInUp',
    'home-review-card--expandable' => $expandable,
]) @if ($expandable) data-review-card-expandable @endif>
    <div class="home-review-quote" aria-hidden="true">“</div>
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
