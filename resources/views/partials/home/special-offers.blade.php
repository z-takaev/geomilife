<!-- Special Offers -->
@php
    $products = [
        [
            'image' => 'oil-product-card.png',
            'name' => __('Масло льняное холодного отжима'),
            'price' => '456',
            'oldPrice' => '539',
            'badge' => __('Хит'),
            'badgeVariant' => 'hit',
            'weights' => [__('100 мл'), __('250 мл'), __('500 мл'), __('1 л')],
        ],
        [
            'image' => 'mountain-honey-card.png',
            'name' => __('Мёд горный натуральный'),
            'price' => '620',
            'oldPrice' => null,
            'badge' => __('Новинка'),
            'badgeVariant' => 'new',
            'weights' => [__('250 г'), __('500 г'), __('1 кг')],
        ],
        [
            'image' => 'walnut-urbech-card.png',
            'name' => __('Урбеч из грецкого ореха'),
            'price' => '389',
            'oldPrice' => '450',
            'badge' => __('Скидка'),
            'badgeVariant' => 'sale',
            'weights' => [__('200 г'), __('350 г')],
        ],
        [
            'image' => 'herbal-tea-card.png',
            'name' => __('Чай травяной «Горный сбор»'),
            'price' => '275',
            'oldPrice' => null,
            'badge' => __('Хит'),
            'badgeVariant' => 'hit',
            'weights' => [__('50 г'), __('100 г'), __('150 г'), __('200 г')],
        ],
        [
            'image' => 'oil-product-card.png',
            'name' => __('Масло чёрного тмина нерафинированное'),
            'price' => '790',
            'oldPrice' => null,
            'badge' => __('Новинка'),
            'badgeVariant' => 'new',
            'weights' => [__('100 мл'), __('250 мл'), __('500 мл')],
        ],
        [
            'image' => 'mountain-honey-card.png',
            'name' => __('Мёд луговой цветочный'),
            'price' => '540',
            'oldPrice' => '610',
            'badge' => __('Скидка'),
            'badgeVariant' => 'sale',
            'weights' => [__('300 г'), __('600 г')],
        ],
    ];
@endphp

<section class="home-content-section">
    <div class="container">
        <div class="sect-head type-2 mb-30 wow fadeInUp">
            <h4 class="s-title text-primary">{{ $title }}</h4>
            <a href="#" class="tf-btn-line pb-0">{{ __('Все товары') }}</a>
        </div>
        <div class="swiper tf-swiper wow fadeInUp" data-preview="5" data-laptop="5" data-tablet="3" data-mobile-sm="2"
            data-mobile="2" data-space-lg="16" data-space-md="10" data-space="10" data-auto="true" data-delay="4000"
            data-loop="true">
            <div class="swiper-wrapper">
                @foreach ($productOrder as $productIndex)
                    @php($product = $products[$productIndex])
                    <x-product-card :image="$product['image']" :name="$product['name']" :price="$product['price']" :old-price="$product['oldPrice']"
                        :badge="$product['badge']" :badge-variant="$product['badgeVariant']" :weights="$product['weights']" />
                @endforeach
            </div>
        </div>
    </div>
</section>
<!-- /Special Offers -->
