<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\News;
use App\Support\Media\FixtureImages;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<News>
 */
final class NewsFactory extends Factory
{
    /**
     * @var list<array{title: string, excerpt: string, content: string, published_days_ago: int}>
     */
    private const array NEWS = [
        [
            'title' => 'Открыли доставку заказов по всей России',
            'excerpt' => 'Продукцию GeoMiLife теперь можно заказать с доставкой в регионы России.',
            'content' => '<p>Мы расширили географию доставки и начали отправлять заказы по всей России.</p><p>Срок и стоимость перевозки рассчитываются при оформлении и зависят от выбранного города.</p>',
            'published_days_ago' => 3,
        ],
        [
            'title' => 'Выпустили новую партию льняного масла',
            'excerpt' => 'Свежая партия масла холодного отжима уже доступна для заказа.',
            'content' => '<p>Для новой партии мы отобрали свежие семена льна и бережно отжали масло при низкой температуре.</p><p>Готовый продукт разлит в тёмные бутылки, которые защищают его от света.</p>',
            'published_days_ago' => 12,
        ],
        [
            'title' => 'Обновили упаковку натурального мёда',
            'excerpt' => 'Новая банка стала удобнее, а этикетка — информативнее.',
            'content' => '<p>Мы сохранили привычный продукт и обновили его оформление.</p><p>На этикетке теперь проще найти сведения о составе, происхождении и условиях хранения мёда.</p>',
            'published_days_ago' => 24,
        ],
        [
            'title' => 'GeoMiLife примет участие в ярмарке местных производителей',
            'excerpt' => 'В выходные представим масла, урбеч и мёд на городской ярмарке во Владикавказе.',
            'content' => '<p>Приглашаем познакомиться с нашей продукцией и задать вопросы о производстве.</p><p>На стенде можно будет попробовать несколько видов масел и урбеча.</p>',
            'published_days_ago' => 36,
        ],
        [
            'title' => 'Расширили ассортимент полезных сладостей',
            'excerpt' => 'В каталоге появились новые сладости с понятным составом и натуральными ингредиентами.',
            'content' => '<p>Мы добавили несколько новых позиций на основе орехов, семян и сухофруктов.</p><p>Подробный состав и рекомендации по хранению доступны в карточках товаров.</p>',
            'published_days_ago' => 51,
        ],
    ];

    public function configure(): static
    {
        return $this->afterCreating(function (News $news): void {
            app(FixtureImages::class)->attachRandom($news, 'News');
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var array{title: string, excerpt: string, content: string, published_days_ago: int} $news */
        $news = fake()->unique()->randomElement(self::NEWS);

        return [
            'title' => $news['title'],
            'slug' => Str::slug($news['title']),
            'excerpt' => $news['excerpt'],
            'content' => $news['content'],
            'published_at' => now()->subDays($news['published_days_ago']),
            'is_active' => true,
        ];
    }
}
