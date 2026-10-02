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
                <x-promotion-card image="blog-5.jpg" :image-alt="__('Набор натуральных продуктов')" :date="__('До 15 октября')" :title="__('Соберите полезный набор со скидкой 15%')"
                    :description="__('Выберите любимые масла, урбеч и мёд — скидка применится к набору автоматически.')" />
                <x-promotion-card image="blog-6.jpg" :image-alt="__('Натуральные продукты GeoMiLife')" :date="__('До 20 октября')" :title="__('Второе сыродавленное масло дешевле на 20%')"
                    :description="__('Добавьте в корзину любые две бутылки масла и получите скидку на вторую позицию.')" />
                <x-promotion-card image="blog-12.jpg" :image-alt="__('Подарок к заказу GeoMiLife')" :date="__('Весь октябрь')" :title="__('Полезный подарок к заказу от 3 000 ₽')"
                    :description="__('Оформите заказ на сумму от 3 000 ₽ и получите натуральный комплимент от GeoMiLife.')" />
            </div>
        </div>
        <div class="home-offers-all text-center wow fadeInUp">
            <a href="#" class="home-offers-all-link">
                {{ __('Смотреть все акции') }}
                <i class="icon icon-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>
<!-- /Latest Blog -->
