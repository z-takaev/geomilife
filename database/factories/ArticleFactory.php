<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use App\Support\Media\FixtureImages;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

/**
 * @extends Factory<Article>
 */
final class ArticleFactory extends Factory
{
    /**
     * @var list<array{title: string, excerpt: string, content: string, published_days_ago: int}>
     */
    private const array ARTICLES = [
        [
            'title' => 'Польза сыродавленных масел в ежедневном рационе',
            'excerpt' => 'Рассказываем, как натуральные масла дополняют рацион и помогают сохранить вкус и пользу семян.',
            'content' => '<p>Сыродавленные масла производят без интенсивного нагрева, поэтому они сохраняют естественный аромат и свойства исходного сырья.</p><p>Добавляйте масло в салаты, каши и готовые блюда небольшими порциями. Так вкус продукта раскрывается лучше всего.</p>',
            'published_days_ago' => 7,
        ],
        [
            'title' => 'Как выбрать натуральное масло для разных блюд',
            'excerpt' => 'Подбираем масло для салатов, каш, овощей и домашней выпечки по вкусу и аромату.',
            'content' => '<p>Льняное масло хорошо сочетается с овощами и кашами, кунжутное добавляет блюдам ореховые ноты, а горчичное подчёркивает вкус салатов.</p><p>Натуральные масла лучше использовать без сильного нагрева и хранить согласно рекомендациям на упаковке.</p>',
            'published_days_ago' => 18,
        ],
        [
            'title' => 'Урбеч: традиционный продукт из семян и орехов',
            'excerpt' => 'Знакомимся с урбечем и простыми способами добавить его в привычное меню.',
            'content' => '<p>Урбеч получают из тщательно перетёртых семян или орехов без лишних добавок. У него насыщенный вкус и плотная однородная текстура.</p><p>Его можно подавать к фруктам, добавлять в кашу или смешивать с мёдом для домашнего десерта.</p>',
            'published_days_ago' => 31,
        ],
        [
            'title' => 'Как правильно хранить масла холодного отжима',
            'excerpt' => 'Простые правила, которые помогают дольше сохранить свежесть, аромат и вкус натурального масла.',
            'content' => '<p>После открытия бутылку следует плотно закрывать и держать в прохладном тёмном месте. Прямой солнечный свет и тепло ускоряют окисление.</p><p>Всегда учитывайте рекомендации на этикетке: для некоторых масел оптимальным местом хранения будет холодильник.</p>',
            'published_days_ago' => 45,
        ],
        [
            'title' => 'Полезный завтрак с натуральными продуктами',
            'excerpt' => 'Три идеи с маслами, мёдом и урбечем для сытного и разнообразного начала дня.',
            'content' => '<p>Добавьте ложку льняного масла в тёплую кашу, подайте цельнозерновой хлеб с урбечем или дополните творог небольшим количеством натурального мёда.</p><p>Такие сочетания легко менять по сезону, используя свежие ягоды, фрукты и орехи.</p>',
            'published_days_ago' => 60,
        ],
    ];

    public function configure(): static
    {
        return $this->afterCreating(function (Article $article): void {
            app(FixtureImages::class)->attachRandom(
                model: $article,
                directory: 'Article',
                processImage: static fn (Image $image): Image => $image->fit(Fit::Crop, 1536, 1024),
            );
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var array{title: string, excerpt: string, content: string, published_days_ago: int} $article */
        $article = fake()->unique()->randomElement(self::ARTICLES);

        return [
            'title' => $article['title'],
            'slug' => Str::slug($article['title']),
            'excerpt' => $article['excerpt'],
            'content' => $article['content'],
            'published_at' => now()->subDays($article['published_days_ago']),
            'is_active' => true,
        ];
    }
}
