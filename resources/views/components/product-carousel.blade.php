@props([
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

<div
    {{ $attributes->class(['swiper', 'tf-swiper'])->merge([
        'data-preview' => $preview,
        'data-laptop' => $laptop,
        'data-tablet' => $tablet,
        'data-mobile-sm' => $mobileSm,
        'data-mobile' => $mobile,
        'data-space-lg' => $spaceLg,
        'data-space-md' => $spaceMd,
        'data-space' => $space,
        'data-auto' => $auto ? 'true' : 'false',
        'data-delay' => $delay,
        'data-loop' => $loop ? 'true' : 'false',
    ]) }}>
    <div class="swiper-wrapper">
        {{ $slot }}
    </div>
</div>
