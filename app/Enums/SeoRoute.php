<?php

declare(strict_types=1);

namespace App\Enums;

enum SeoRoute: string
{
    case Home = 'home';
    case Contacts = 'contacts.index';
    case Faqs = 'faqs.index';
    case News = 'news.index';
    case Articles = 'articles.index';
    case Promos = 'promos.index';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Главная',
            self::Contacts => 'Контакты',
            self::Faqs => 'Вопросы и ответы',
            self::News => 'Новости',
            self::Articles => 'Статьи',
            self::Promos => 'Акции',
        };
    }

    public function title(): string
    {
        return $this === self::Home
            ? 'GeoMiLife — натуральные продукты из Северной Осетии'
            : $this->label();
    }

    public function description(): string
    {
        return match ($this) {
            self::Home => 'Натуральные продукты GeoMiLife с доставкой по России.',
            self::Contacts => 'Контактная информация GeoMiLife и форма обратной связи.',
            self::Faqs => 'Ответы на частые вопросы о продукции GeoMiLife, оформлении заказа, оплате и доставке.',
            self::News => 'Новости производства, новые продукты и важные события GeoMiLife.',
            self::Articles => 'Полезные статьи о натуральных продуктах, питании и здоровых привычках.',
            self::Promos => 'Акции и специальные предложения на натуральные продукты GeoMiLife.',
        };
    }
}
