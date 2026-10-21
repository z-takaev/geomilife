<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Category;
use App\Navigation\MenuItem;
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
            MenuItem::make('Акции')->url(route('promos.index')),
            MenuItem::make('Доставка и оплата')->url(route('pages.show', 'delivery')),
            MenuItem::make('О нас')->url(route('pages.show', 'about')),
            MenuItem::make('Контакты')->url(route('contacts.index')),
            MenuItem::make('Еще')->children([
                MenuItem::make('Вопросы и ответы')->url(route('faqs.index')),
                MenuItem::make('Статьи')->url(route('articles.index')),
                MenuItem::make('Новости')->url(route('news.index')),
                MenuItem::make('Отзывы')->url(route('reviews.index')),
                MenuItem::make('Наше производство')->url(route('pages.show', 'production')),
                MenuItem::make('Контроль качества')->url(route('pages.show', 'quality-control')),
                MenuItem::make('Возврат и обмен')->url(route('pages.show', 'returns')),
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
                MenuItem::make('Каталог')->url(route('catalog.index')),
                MenuItem::make('Акции')->url(route('promos.index')),
                MenuItem::make('Статьи')->url(route('articles.index')),
                MenuItem::make('Новости')->url(route('news.index')),
                MenuItem::make('Отзывы')->url(route('reviews.index')),
                MenuItem::make('Контакты')->url(route('contacts.index')),
            ]),
            MenuItem::make('Информация')->children([
                MenuItem::make('О нас')->url(route('pages.show', 'about')),
                MenuItem::make('Наше производство')->url(route('pages.show', 'production')),
                MenuItem::make('Контроль качества')->url(route('pages.show', 'quality-control')),
                MenuItem::make('Доставка и оплата')->url(route('pages.show', 'delivery')),
                MenuItem::make('Возврат и обмен')->url(route('pages.show', 'returns')),
                MenuItem::make('Вопросы и ответы')->url(route('faqs.index')),
            ]),
        ];
    }

    /**
     * @return list<MenuItem>
     */
    private function catalog(CategoryRepository $categoryRepository): array
    {
        return $categoryRepository->getActiveCatalog()
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
