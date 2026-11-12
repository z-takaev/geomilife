<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Filament\Resources\ShippingBoxes\Pages\CreateShippingBox;
use App\Filament\Resources\ShippingBoxes\Pages\EditShippingBox;
use App\Filament\Resources\ShippingBoxes\Pages\ListShippingBoxes;
use App\Filament\Resources\ShippingBoxes\ShippingBoxResource;
use App\Models\FilamentUser;
use App\Models\ShippingBox;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->actingAs(FilamentUser::query()->create([
        'name' => 'Администратор',
        'email' => 'admin@example.test',
        'password' => 'password',
        'role' => FilamentUserRoleEnum::ADMIN,
    ]), 'filament');

    $this->boxData = [
        'name' => 'Коробка',
        'length' => 100,
        'width' => 100,
        'height' => 100,
        'weight' => 10,
        'is_active' => false,
    ];
});

it('разрешает создать десятую упаковку', function (): void {
    ShippingBox::query()->insert(array_fill(0, 9, $this->boxData));

    Livewire::test(ListShippingBoxes::class)->assertActionVisible('create');
    Livewire::test(CreateShippingBox::class)
        ->fillForm($this->boxData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseCount('shipping_boxes', 10);
});

it('запрещает создание при достижении лимита с учетом неактивных упаковок', function (int $count): void {
    ShippingBox::query()->insert(array_fill(0, $count, $this->boxData));

    expect(ShippingBoxResource::canCreate())->toBeFalse();
    Livewire::test(ListShippingBoxes::class)->assertActionHidden('create');
    $this->get(ShippingBoxResource::getUrl('create'))->assertForbidden();

    $this->assertDatabaseCount('shipping_boxes', $count);
})->with([10, 11]);

it('запрещает сохранение ранее открытой формы после достижения лимита', function (): void {
    ShippingBox::query()->insert(array_fill(0, 9, $this->boxData));
    $component = Livewire::test(CreateShippingBox::class)->fillForm($this->boxData);

    ShippingBox::query()->create($this->boxData);

    $component->call('create')->assertForbidden();

    $this->assertDatabaseCount('shipping_boxes', 10);
});

it('разрешает редактировать упаковку при достижении лимита', function (): void {
    ShippingBox::query()->insert(array_fill(0, 10, $this->boxData));
    $box = ShippingBox::query()->firstOrFail();

    Livewire::test(EditShippingBox::class, ['record' => $box->getKey()])
        ->fillForm(['name' => 'Обновленная коробка'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($box->fresh()->name)->toBe('Обновленная коробка');
    $this->assertDatabaseCount('shipping_boxes', 10);
});

it('снова разрешает создание после удаления упаковки', function (): void {
    ShippingBox::query()->insert(array_fill(0, 10, $this->boxData));
    ShippingBox::query()->firstOrFail()->delete();

    Livewire::test(CreateShippingBox::class)
        ->fillForm($this->boxData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseCount('shipping_boxes', 10);
});

it('не принимает габариты коробки меньше одиннадцати миллиметров', function (string $field): void {
    Livewire::test(CreateShippingBox::class)
        ->fillForm([...$this->boxData, $field => 10])
        ->call('create')
        ->assertHasFormErrors([$field => 'min']);

    $this->assertDatabaseCount('shipping_boxes', 0);
})->with(['length', 'width', 'height']);
