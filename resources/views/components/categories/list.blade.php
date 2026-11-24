@props(['categories', 'animated' => false])

@php
    $backgrounds = [
        'bg-peach-blush',
        'bg-pale-cream',
        'bg-mint-whisper',
        'bg-ice-blue',
        'bg-lavender-mist',
        'bg-lilac-cloud',
    ];
@endphp

<div {{ $attributes->class(['featured-categories-grid', 'wow fadeInUp' => $animated]) }}>
    @foreach ($categories as $category)
        @php
            $productsCount = $category->total_active_products_count;
        @endphp

        <x-categories.card :background="$backgrounds[$loop->index % count($backgrounds)]" :image="$category->getFirstMediaUrl('image')" :name="$category->name" :product-count="trans_choice(
            '{0} Нет товаров|{1} :count товар|[2,4] :count товара|[5,*] :count товаров',
            $productsCount,
            ['count' => $productsCount],
        )" :url="route('catalog.category', $category)" />
    @endforeach
</div>
