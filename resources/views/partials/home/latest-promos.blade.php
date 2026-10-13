<!-- Latest Blog -->
<section id="blog" class="home-content-section home-offers-section">
    <div class="container">
        <div class="sect-head text-center wow fadeInUp">
            <h2 class="s-title text-primary mb-16 text-center">{{ __('Акции и спец. предложения') }}</h2>
            <p class="s-desc text-body-large">
                {{ __('Больше пользы по приятной цене — выбирайте специальные предложения GeoMiLife.') }}
            </p>
        </div>
        <div class="home-offers-scroll">
            <div class="home-offers-list">
                @foreach ($promos as $promo)
                    <x-promotion-card :date="__('До :date', ['date' => $promo->end_at->translatedFormat('j F Y')])" :description="$promo->excerpt ?? ''" :image="$promo->getFirstMediaUrl('image', 'card')" :image-alt="$promo->title"
                        :title="$promo->title" :url="route('promos.show', $promo)" />
                @endforeach
            </div>
        </div>
        <div class="home-offers-all text-center wow fadeInUp">
            <a href="{{ route('promos.index') }}" class="home-offers-all-link">
                {{ __('Смотреть все акции') }}
                <i class="icon icon-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>
<!-- /Latest Blog -->
