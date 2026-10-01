<!-- Special Offers -->
@php
    $products = [
        [
            'image' => 'oil-product-card.png',
            'name' => 'Масло льняное холодного отжима',
            'price' => '456',
            'oldPrice' => '539',
            'badge' => 'Хит',
            'badgeVariant' => 'hit',
            'weights' => ['100 мл', '250 мл', '500 мл', '1 л'],
        ],
        [
            'image' => 'mountain-honey-card.png',
            'name' => 'Мёд горный натуральный',
            'price' => '620',
            'oldPrice' => null,
            'badge' => 'Новинка',
            'badgeVariant' => 'new',
            'weights' => ['250 г', '500 г', '1 кг'],
        ],
        [
            'image' => 'walnut-urbech-card.png',
            'name' => 'Урбеч из грецкого ореха',
            'price' => '389',
            'oldPrice' => '450',
            'badge' => 'Скидка',
            'badgeVariant' => 'sale',
            'weights' => ['200 г', '350 г'],
        ],
        [
            'image' => 'herbal-tea-card.png',
            'name' => 'Чай травяной «Горный сбор»',
            'price' => '275',
            'oldPrice' => null,
            'badge' => 'Хит',
            'badgeVariant' => 'hit',
            'weights' => ['50 г', '100 г', '150 г', '200 г'],
        ],
        [
            'image' => 'oil-product-card.png',
            'name' => 'Масло чёрного тмина нерафинированное',
            'price' => '790',
            'oldPrice' => null,
            'badge' => 'Новинка',
            'badgeVariant' => 'new',
            'weights' => ['100 мл', '250 мл', '500 мл'],
        ],
        [
            'image' => 'mountain-honey-card.png',
            'name' => 'Мёд луговой цветочный',
            'price' => '540',
            'oldPrice' => '610',
            'badge' => 'Скидка',
            'badgeVariant' => 'sale',
            'weights' => ['300 г', '600 г'],
        ],
    ];
@endphp

<section class="home-content-section">
    <div class="container">
        <div class="sect-head type-2 mb-30 wow fadeInUp">
            <h4 class="s-title text-primary">{{ $title }}</h4>
            <a href="#" class="tf-btn-line pb-0">Все товары</a>
        </div>
        <div class="swiper tf-swiper wow fadeInUp" data-preview="5" data-laptop="5" data-tablet="3"
            data-mobile-sm="2" data-mobile="2" data-space-lg="16" data-space-md="10" data-space="10"
            data-auto="true" data-delay="4000" data-loop="true">
            <div class="swiper-wrapper">
                @foreach ($productOrder as $productIndex)
                    @php($product = $products[$productIndex])
                    <x-product-card :image="$product['image']" :name="$product['name']" :price="$product['price']"
                        :old-price="$product['oldPrice']" :badge="$product['badge']" :badge-variant="$product['badgeVariant']"
                        :weights="$product['weights']" />
                @endforeach
            </div>
        </div>
    </div>
</section>
<!-- /Special Offers -->
