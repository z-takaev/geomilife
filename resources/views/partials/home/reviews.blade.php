@if ($reviews->isNotEmpty())
    <section class="home-content-section home-reviews">
        <div class="container">
            <div class="sect-head type-2 mb-30 wow fadeInUp">
                <h2 class="s-title text-primary">
                    <span class="d-none d-sm-inline">{{ __('Отзывы клиентов') }}</span>
                    <span class="d-sm-none">{{ __('Отзывы') }}</span>
                </h2>
                <a href="{{ route('reviews.index') }}" class="tf-btn-line pb-0">
                    <span class="d-none d-sm-inline">{{ __('Все отзывы') }}</span>
                    <span class="d-sm-none">{{ __('Все') }}</span>
                </a>
            </div>

            <div class="home-reviews-grid">
                @foreach ($reviews as $review)
                    <x-review-card :review="$review" />
                @endforeach
            </div>
        </div>
    </section>
@endif
