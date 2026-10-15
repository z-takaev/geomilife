@if ($reviews->isNotEmpty())
    <section class="home-content-section home-reviews">
        <div class="container">
            <div class="sect-head type-2 mb-30 wow fadeInUp">
                <h2 class="s-title text-primary">{{ __('Отзывы клиентов') }}</h2>
                <a href="{{ route('reviews.index') }}" class="tf-btn-line pb-0">
                    <span class="d-none d-sm-inline">{{ __('Все отзывы') }}</span>
                    <span class="d-sm-none">{{ __('Все') }}</span>
                </a>
            </div>

            <div class="home-reviews-grid">
                @foreach ($reviews as $review)
                    <article class="home-review-card wow fadeInUp" @style(['--review-delay: ' . ($loop->iteration - 1) * 0.1 . 's'])>
                        <div class="home-review-quote" aria-hidden="true">“</div>
                        <p class="home-review-text">{{ $review->text }}</p>
                        <footer class="home-review-footer">
                            <p class="home-review-author">{{ $review->author_name }}</p>
                            <time class="home-review-date" datetime="{{ $review->reviewed_at->toDateString() }}">
                                {{ $review->reviewed_at->translatedFormat('j F Y') }}
                            </time>
                        </footer>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
