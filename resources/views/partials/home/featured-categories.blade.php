<!-- Featured Categories -->
@php
    $categories = [
        ['name' => __('Масла'), 'image' => 'oils.png', 'background' => 'bg-peach-blush'],
        ['name' => __('Мёд и продукты пчеловодства'), 'image' => 'honey-products.png', 'background' => 'bg-pale-cream'],
        ['name' => __('Жмых / мука'), 'image' => 'oil-cake-flour.png', 'background' => 'bg-mint-whisper'],
        ['name' => __('Семена'), 'image' => 'seeds.png', 'background' => 'bg-ice-blue'],
        ['name' => __('Урбеч'), 'image' => 'urbech.png', 'background' => 'bg-lavender-mist'],
        ['name' => __('БАД'), 'image' => 'supplements.png', 'background' => 'bg-lilac-cloud'],
        ['name' => __('Про- и Пребиотики'), 'image' => 'pro-prebiotics.png', 'background' => 'bg-pale-cream'],
        ['name' => __('Грибы'), 'image' => 'mushrooms.png', 'background' => 'bg-mint-whisper'],
        ['name' => __('Специи'), 'image' => 'spices.png', 'background' => 'bg-ice-blue'],
        ['name' => __('Полезные сладости'), 'image' => 'healthy-sweets.png', 'background' => 'bg-lavender-mist'],
        ['name' => __('Бакалея'), 'image' => 'groceries.png', 'background' => 'bg-lilac-cloud'],
        ['name' => __('Средства'), 'image' => 'care-products.png', 'background' => 'bg-peach-blush'],
    ];
@endphp

<section id="catalog" class="section-category home-content-section">
    <div class="container">
        <div class="sect-head type-2 mb-30 wow fadeInUp">
            <h4 class="s-title text-primary">{{ __('Наш каталог') }}</h4>
            <a href="#" class="tf-btn-line pb-0">
                <span class="d-none d-sm-inline">{{ __('Все категории') }}</span>
                <span class="d-sm-none">{{ __('Все') }}</span>
            </a>
        </div>
        <div class="featured-categories-grid wow fadeInUp">
            @foreach ($categories as $category)
                <x-category-card :background="$category['background']" :image="$category['image']" :name="$category['name']" />
            @endforeach
        </div>
    </div>
</section>
<!-- /Featured Categories -->
