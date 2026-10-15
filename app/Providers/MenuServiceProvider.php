<?php

declare(strict_types=1);

namespace App\Providers;

use App\Menu\MenuItem;
use App\Models\Category;
use App\Repositories\Categories\CategoryRepository;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

final class MenuServiceProvider extends ServiceProvider
{
    public function boot(CategoryRepository $categoryRepository): void
    {
        View::composer('partials.navigation', function (ViewInstance $view) use ($categoryRepository): void {
            $view->with('items', $this->header($categoryRepository));
        });

        View::composer('partials.footer-navigation', function (ViewInstance $view): void {
            $view->with('columns', $this->footer());
        });
    }

    /**
     * @return list<MenuItem>
     */
    private function header(CategoryRepository $categoryRepository): array
    {
        return [
            MenuItem::make('Каталог')->isCatalog()->children($this->catalog($categoryRepository)),
            MenuItem::make('Акции'),
            MenuItem::make('Доставка и оплата')->url(route('pages.show', 'delivery')),
            MenuItem::make('О нас')->url(route('pages.show', 'about')),
            MenuItem::make('Контакты')->url(route('contacts.index')),
            MenuItem::make('Еще')->children([
                MenuItem::make('Вопрос-ответ')->url(route('faqs.index')),
                MenuItem::make('Our Services')->children([
                    MenuItem::make('Our Services 1'),
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
                MenuItem::make('Контакты')->url(route('contacts.index')),
            ]),
            MenuItem::make('Информация')->children([
                MenuItem::make('О нас')->url(route('pages.show', 'about')),
                MenuItem::make('Доставка и оплата')->url(route('pages.show', 'delivery')),
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
    private function catalog(CategoryRepository $categoryRepository): array
    {
        return $categoryRepository->getActiveTree()
            ->map(fn (Category $category): MenuItem => $this->categoryMenuItem($category))
            ->all();
    }

    private function categoryMenuItem(Category $category): MenuItem
    {
        return MenuItem::make($category->name)->children(
            $category->children
                ->map(fn (Category $child): MenuItem => $this->categoryMenuItem($child))
                ->all(),
        );
    }
}
