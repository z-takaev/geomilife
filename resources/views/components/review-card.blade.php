@props(['review', 'delay' => 0])

<article class="home-review-card wow fadeInUp" @style(['--review-delay: ' . $delay . 's'])>
    <div class="home-review-quote" aria-hidden="true">“</div>
    <p class="home-review-text">{{ $review->text }}</p>
    <footer class="home-review-footer">
        <p class="home-review-author">{{ $review->author_name }}</p>
        <time class="home-review-date" datetime="{{ $review->reviewed_at->toDateString() }}">
            {{ $review->reviewed_at->translatedFormat('j F Y') }}
        </time>
    </footer>
</article>
