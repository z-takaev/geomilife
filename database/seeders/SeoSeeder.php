<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Seo;
use Illuminate\Database\Seeder;

final class SeoSeeder extends Seeder
{
    private const array ROUTE_DESCRIPTIONS = [
        'home' => 'Натуральные продукты GeoMiLife из Северной Осетии: сыродавленные масла, урбеч, мёд, семена и другие продукты с доставкой по России.',
        'catalog.index' => 'Каталог натуральных продуктов GeoMiLife: масла, урбеч, мёд, семена, полезные сладости и другие товары с доставкой по России.',
        'contacts' => 'Контакты GeoMiLife: способы связи, адрес, график работы и информация для покупателей.',
        'faqs' => 'Ответы на частые вопросы о продукции GeoMiLife, оформлении заказа, оплате, доставке, хранении и возврате товаров.',
        'reviews' => 'Отзывы покупателей о натуральных продуктах GeoMiLife, качестве обслуживания и доставке заказов.',
        'news.index' => 'Новости GeoMiLife: новые продукты, свежие партии, изменения в доставке, события компании и важная информация для покупателей.',
        'articles.index' => 'Статьи GeoMiLife о натуральных продуктах, сыродавленных маслах, урбече, мёде, выборе, использовании и правильном хранении.',
        'promos.index' => 'Актуальные акции и специальные предложения GeoMiLife на натуральные продукты из Северной Осетии.',
    ];

    private const array PAGE_DESCRIPTIONS = [
        'production' => 'Узнайте, как GeoMiLife производит натуральные масла и другие продукты, отбирает сырьё и контролирует каждый этап производства.',
        'delivery_payment' => 'Условия доставки и оплаты заказов GeoMiLife: доступные способы получения, сроки, стоимость и варианты оплаты.',
        'return_exchange' => 'Условия возврата и обмена товаров GeoMiLife, порядок обращения и информация, необходимая для оформления возврата.',
        'about' => 'История GeoMiLife, принципы производства и подход к созданию натуральных продуктов из Северной Осетии.',
        'quality_control' => 'Как GeoMiLife контролирует качество сырья и готовой продукции на этапах производства, упаковки и хранения.',
        'confidentiality' => 'Информация о сборе, обработке и защите персональных данных пользователей сайта GeoMiLife.',
        'policy' => 'Правила использования сайта GeoMiLife, оформления заказов и взаимодействия между покупателем и продавцом.',
    ];

    public function run(): void
    {
        foreach (self::ROUTE_DESCRIPTIONS as $routeName => $description) {
            $seo = Seo::query()->firstOrCreate(
                ['route_name' => $routeName],
                ['title' => null],
            );

            $this->fillDescription($seo, $description);
        }

        $pages = Page::query()
            ->whereIn('key', array_keys(self::PAGE_DESCRIPTIONS))
            ->get()
            ->keyBy('key');

        foreach (self::PAGE_DESCRIPTIONS as $pageKey => $description) {
            $page = $pages->get($pageKey);

            if ($page === null) {
                continue;
            }

            $this->fillDescription($page->seo()->firstOrCreate(), $description);
        }
    }

    private function fillDescription(Seo $seo, string $description): void
    {
        if (filled($seo->description)) {
            return;
        }

        $seo->update(['description' => $description]);
    }
}
