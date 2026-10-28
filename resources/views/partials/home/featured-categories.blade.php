@php
    $backgrounds = [
        'bg-peach-blush',
        'bg-pale-cream',
        'bg-mint-whisper',
        'bg-ice-blue',
        'bg-lavender-mist',
        'bg-lilac-cloud',
        'bg-pale-cream',
        'bg-mint-whisper',
        'bg-ice-blue',
        'bg-lavender-mist',
        'bg-lilac-cloud',
        'bg-peach-blush',
    ];
@endphp

<section id="catalog" class="section-category home-content-section">
    <div class="container">
        <div class="sect-head type-2 mb-30 wow fadeInUp">
            <h4 class="s-title text-primary">{{ __('Наш каталог') }}</h4>
            <a href="{{ route('catalog.index') }}" class="tf-btn-line pb-0">
                <span class="d-none d-sm-inline">{{ __('Все категории') }}</span>
                <span class="d-sm-none">{{ __('Все') }}</span>
            </a>
        </div>
        <div class="featured-categories-grid wow fadeInUp">
            @foreach ($featuredCategories as $category)
                @php
                    $productsCount = $category->total_active_products_count;
                @endphp
                <x-category-card :background="$backgrounds[$loop->index]" :image="$category->getFirstMediaUrl('image')" :name="$category->name" :product-count="trans_choice(
                    '{0} Нет товаров|{1} :count товар|[2,4] :count товара|[5,*] :count товаров',
                    $productsCount,
                    ['count' => $productsCount],
                )"
                    :url="route('catalog.category', $category)" />
            @endforeach
        </div>
    </div>
</section>
