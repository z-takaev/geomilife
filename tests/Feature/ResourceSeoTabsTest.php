<?php

declare(strict_types=1);

use App\Filament\Resources\Articles\Schemas\ArticleForm;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\News\Schemas\NewsForm;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Promos\Schemas\PromoForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

it('выносит seo в отдельную вкладку формы ресурса', function (string $formClass, string $mainField): void {
    $livewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };
    $schema = $formClass::configure(Schema::make($livewire));
    $tabs = collect($schema->getComponents(withHidden: true))
        ->sole(static fn (object $component): bool => $component instanceof Tabs);
    $tabComponents = collect($tabs->getChildSchema()->getComponents(withHidden: true));

    expect($tabs->isTabPersistedInQueryString())->toBeTrue()
        ->and($tabComponents)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(Tab::class)
        ->and($tabComponents->map(static fn (Tab $tab): string => (string) $tab->getLabel())->all())
        ->toBe(['Основное', 'SEO']);

    $mainTab = $tabComponents->first();
    $seoTab = $tabComponents->last();
    $seoSection = collect($seoTab->getChildSchema()->getComponents(withHidden: true))
        ->sole(static fn (object $component): bool => $component instanceof Section);

    expect(array_keys($mainTab->getChildSchema()->getFlatComponents(withHidden: true)))
        ->toContain($mainField)
        ->and($seoSection->getRelationshipName())->toBe('seo');
})->with([
    'категории' => [CategoryForm::class, 'name'],
    'целевые страницы' => [PageForm::class, 'title'],
    'акции' => [PromoForm::class, 'title'],
    'статьи' => [ArticleForm::class, 'title'],
    'новости' => [NewsForm::class, 'title'],
]);
