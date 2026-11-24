<section id="catalog" class="section-category home-content-section">
    <div class="container">
        <div class="sect-head type-2 mb-30 wow fadeInUp">
            <h4 class="s-title text-primary">{{ __('Наш каталог') }}</h4>
            <a href="{{ route('catalog.index') }}" class="tf-btn-line pb-0">
                <span class="d-none d-sm-inline">{{ __('Все категории') }}</span>
                <span class="d-sm-none">{{ __('Все') }}</span>
            </a>
        </div>

        <x-categories.list :categories="$featuredCategories" animated />
    </div>
</section>
