<?php

declare(strict_types=1);

use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

it('определяет отдельные классы схем для infolist ресурсов', function (string $infolistClass): void {
    expect($infolistClass)
        ->toBeClass()
        ->toHaveMethod('configure');

    $configureMethod = new ReflectionMethod($infolistClass, 'configure');

    expect($configureMethod->isPublic())->toBeTrue()
        ->and($configureMethod->isStatic())->toBeTrue()
        ->and($configureMethod->getParameters())->toHaveCount(1)
        ->and($configureMethod->getParameters()[0]->getType()?->getName())->toBe(Schema::class)
        ->and($configureMethod->getReturnType()?->getName())->toBe(Schema::class);
})->with([
    UserInfolist::class,
    ProductInfolist::class,
    OrderInfolist::class,
]);

it('показывает заглушки для необязательных данных пользователя', function (): void {
    $schema = UserInfolist::configure(Schema::make(infolistLivewireComponent()));
    $components = $schema->getFlatComponents(withHidden: true);

    expect($components['birth_date']->getPlaceholder())->toBe('—')
        ->and($components['telegram_username']->getPlaceholder())->toBe('—');
});

it('показывает заглушки для пустых данных товара', function (string $component, string $placeholder, ?string $parent = null): void {
    $schema = ProductInfolist::configure(Schema::make(infolistLivewireComponent()));
    $components = $schema->getFlatComponents(withHidden: true);

    if ($parent !== null) {
        $components = $components[$parent]->getChildSchema()->getFlatComponents(withHidden: true);
    }

    expect($components[$component]->getPlaceholder())->toBe($placeholder);
})->with([
    ['image', 'Изображение не загружено'],
    ['description', '—'],
    ['gallery', 'Изображения не загружены'],
    ['attributeValues', 'Характеристики не указаны'],
    ['old_price', '—', 'variants'],
    ['weight', '—', 'variants'],
    ['length', '—', 'variants'],
    ['width', '—', 'variants'],
    ['height', '—', 'variants'],
]);

it('располагает характеристики сразу после блока товара', function (): void {
    $schema = ProductInfolist::configure(Schema::make(infolistLivewireComponent()));
    $sectionHeadings = collect($schema->getComponents(withHidden: true))
        ->filter(static fn (object $component): bool => $component instanceof Section)
        ->map(static fn (Section $section): string => (string) $section->getHeading())
        ->values()
        ->all();

    expect($sectionHeadings)->toBe(['Товар', 'Характеристики', 'Варианты']);
});

function infolistLivewireComponent(): Component&HasSchemas
{
    return new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };
}
