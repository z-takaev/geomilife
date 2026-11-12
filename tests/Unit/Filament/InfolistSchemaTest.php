<?php

declare(strict_types=1);

use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Products\Schemas\ProductInfolist;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use Filament\Schemas\Schema;

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
