<?php

declare(strict_types=1);

namespace App\Providers;

use App\Navigation\MenuItem;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

final class MenuServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('partials.navigation', function (ViewInstance $view): void {
            $view->with('items', $this->header());
        });

        View::composer('partials.footer-navigation', function (ViewInstance $view): void {
            $view->with('columns', $this->footer());
        });
    }

    /**
     * @return list<MenuItem>
     */
    private function header(): array
    {
        return [
            MenuItem::make('Каталог')->isCatalog()->children($this->catalog()),
            MenuItem::make('Акции'),
            MenuItem::make('Доставка и оплата'),
            MenuItem::make('О компании'),
            MenuItem::make('Контакты'),
            MenuItem::make('Еще')->children([
                MenuItem::make('About Us'),
                MenuItem::make('Pricing Plans'),
                MenuItem::make('Contact Us'),
                MenuItem::make('FAQs'),
                MenuItem::make('Privacy Policy'),
                MenuItem::make('Our Services')->children([
                    MenuItem::make('Our Services 1'),
                    MenuItem::make('Our Services 2'),
                    MenuItem::make('Our Services 3'),
                    MenuItem::make('Services Details'),
                ]),
            ]),
        ];
    }

    /**
     * @return list<MenuItem>
     */
    private function footer(): array
    {
        return [
            MenuItem::make('Разделы')->children([
                MenuItem::make('Главная'),
                MenuItem::make('Каталог'),
                MenuItem::make('Преимущества'),
                MenuItem::make('Акции'),
                MenuItem::make('Полезные статьи'),
                MenuItem::make('Контакты'),
            ]),
            MenuItem::make('Информация')->children([
                MenuItem::make('О компании'),
                MenuItem::make('Доставка и оплата'),
                MenuItem::make('Гарантия качества'),
                MenuItem::make('Связаться с нами'),
                MenuItem::make('Позвонить нам'),
                MenuItem::make('Написать на почту'),
            ]),
        ];
    }

    /**
     * @return list<MenuItem>
     */
    private function catalog(): array
    {
        return [
            MenuItem::make('Homepage 1'),
            MenuItem::make('Homepage 2'),
            MenuItem::make('Homepage 3'),
            MenuItem::make('Homepage 4'),
            MenuItem::make('Our Services')->children([
                MenuItem::make('Our Services 1'),
                MenuItem::make('Our Services 2'),
                MenuItem::make('Our Services 3'),
                MenuItem::make('Services Details'),
            ]),
        ];
    }
}
