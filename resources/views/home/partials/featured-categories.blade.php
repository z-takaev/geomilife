<!-- Featured Categories -->
@php
    $categories = [
        ['name' => 'Масла', 'image' => 'oils.png', 'background' => 'bg-peach-blush'],
        ['name' => 'Мёд и продукты пчеловодства', 'image' => 'honey-products.png', 'background' => 'bg-pale-cream'],
        ['name' => 'Жмых / мука', 'image' => 'oil-cake-flour.png', 'background' => 'bg-mint-whisper'],
        ['name' => 'Семена', 'image' => 'seeds.png', 'background' => 'bg-ice-blue'],
        ['name' => 'Урбеч', 'image' => 'urbech.png', 'background' => 'bg-lavender-mist'],
        ['name' => 'БАД', 'image' => 'supplements.png', 'background' => 'bg-lilac-cloud'],
        ['name' => 'Про- и Пребиотики', 'image' => 'pro-prebiotics.png', 'background' => 'bg-peach-blush'],
        ['name' => 'Грибы', 'image' => 'mushrooms.png', 'background' => 'bg-pale-cream'],
        ['name' => 'Специи', 'image' => 'spices.png', 'background' => 'bg-mint-whisper'],
        ['name' => 'Полезные сладости', 'image' => 'healthy-sweets.png', 'background' => 'bg-ice-blue'],
        ['name' => 'Бакалея', 'image' => 'groceries.png', 'background' => 'bg-lavender-mist'],
        ['name' => 'Средства', 'image' => 'care-products.png', 'background' => 'bg-lilac-cloud'],
    ];
@endphp

<section class="section-category home-content-section">
    <div class="container">
        <div class="sect-head type-2 mb-30 wow fadeInUp">
            <h4 class="s-title text-primary">Наш каталог</h4>
            <a href="#" class="tf-btn-line pb-0">Все категории</a>
        </div>
        <div class="featured-categories-grid wow fadeInUp">
            @foreach ($categories as $category)
                <a href="#" class="category-v01 hover-img style-2 {{ $category['background'] }}">
                    <div class="cate-image img-style">
                        <img loading="lazy" width="120" height="120"
                            src="{{ asset('assets/images/category/' . $category['image']) }}"
                            alt="{{ $category['name'] }}">
                    </div>
                    <div class="cate-info">
                        <h5 class="info_name text-primary link-underline">{{ $category['name'] }}</h5>
                        <p class="info_quanity text-caption-01">12 товаров</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
<!-- /Featured Categories -->
