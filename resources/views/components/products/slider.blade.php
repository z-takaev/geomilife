@props([
    'title',
    'allProductsUrl',
    'auto' => true,
    'delay' => 4000,
    'laptop' => 5,
    'loop' => true,
    'mobile' => 'auto',
    'mobileSm' => 2,
    'preview' => 5,
    'space' => 10,
    'spaceLg' => 16,
    'spaceMd' => 10,
    'tablet' => 3,
])

<section {{ $attributes->class(['home-content-section']) }}>
    <div class="container">
        <div class="sect-head type-2 mb-30 wow fadeInUp">
            <h4 class="s-title text-primary">{{ $title }}</h4>
            <a href="{{ $allProductsUrl }}" class="tf-btn-line pb-0">
                <span class="d-none d-sm-inline">{{ __('Все товары') }}</span>
                <span class="d-sm-none">{{ __('Все') }}</span>
            </a>
        </div>

        <div class="swiper tf-swiper home-products-slider wow fadeInUp" data-preview="{{ $preview }}"
            data-laptop="{{ $laptop }}" data-tablet="{{ $tablet }}" data-mobile-sm="{{ $mobileSm }}"
            data-mobile="{{ $mobile }}" data-space-lg="{{ $spaceLg }}" data-space-md="{{ $spaceMd }}"
            data-space="{{ $space }}" data-auto="{{ $auto ? 'true' : 'false' }}"
            data-delay="{{ $delay }}" data-loop="{{ $loop ? 'true' : 'false' }}">
            <div class="swiper-wrapper">
                {{ $slot }}
            </div>
        </div>
    </div>
</section>
