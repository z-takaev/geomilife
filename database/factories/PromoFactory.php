<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Promo;
use App\Support\Media\FixtureImages;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Promo>
 */
final class PromoFactory extends Factory
{
    /**
     * @var list<array{title: string, excerpt: string, content: string, starts_in_days: int, duration_days: int}>
     */
    private const array PROMOS = [
        [
            'title' => 'Скидка на набор сыродавленных масел',
            'excerpt' => 'Соберите домашнюю коллекцию натуральных масел на специальных условиях.',
            'content' => '<p>В акционный набор входят льняное, кунжутное и горчичное масла холодного отжима.</p><p>Предложение действует до окончания указанного срока при наличии товара.</p>',
            'starts_in_days' => -5,
            'duration_days' => 30,
        ],
        [
            'title' => 'Мёд в подарок к большому заказу',
            'excerpt' => 'Добавим баночку натурального мёда к заказу от установленной суммы.',
            'content' => '<p>Оформите заказ на сумму, указанную в условиях акции, и получите натуральный мёд в подарок.</p><p>Подарок автоматически добавляется при подтверждении заказа.</p>',
            'starts_in_days' => -2,
            'duration_days' => 21,
        ],
        [
            'title' => 'Неделя полезных завтраков',
            'excerpt' => 'Специальные цены на урбеч, мёд и продукты для вкусного завтрака.',
            'content' => '<p>В течение недели на подборку продуктов для завтрака действуют специальные цены.</p><p>Выбирайте любимый урбеч, натуральный мёд и дополнения к кашам.</p>',
            'starts_in_days' => 1,
            'duration_days' => 7,
        ],
        [
            'title' => 'Бесплатная доставка по Владикавказу',
            'excerpt' => 'Доставим заказ по городу бесплатно при достижении минимальной суммы.',
            'content' => '<p>Бесплатная доставка действует в пределах Владикавказа для заказов от суммы, указанной при оформлении.</p><p>Менеджер согласует удобный интервал после подтверждения заказа.</p>',
            'starts_in_days' => -10,
            'duration_days' => 45,
        ],
        [
            'title' => 'Знакомство с новыми вкусами урбеча',
            'excerpt' => 'Попробуйте набор из трёх видов урбеча по специальной цене.',
            'content' => '<p>Мы собрали три разных вкуса урбеча в один набор, чтобы вы могли выбрать любимый.</p><p>Количество акционных наборов ограничено.</p>',
            'starts_in_days' => 3,
            'duration_days' => 20,
        ],
    ];

    public function configure(): static
    {
        return $this->afterCreating(function (Promo $promo): void {
            app(FixtureImages::class)->attachRandom($promo, 'Promo');
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var array{title: string, excerpt: string, content: string, starts_in_days: int, duration_days: int} $promo */
        $promo = fake()->unique()->randomElement(self::PROMOS);
        $startAt = now()->addDays($promo['starts_in_days']);

        return [
            'title' => $promo['title'],
            'slug' => Str::slug($promo['title']),
            'excerpt' => $promo['excerpt'],
            'content' => $promo['content'],
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addDays($promo['duration_days']),
            'is_active' => true,
        ];
    }
}
