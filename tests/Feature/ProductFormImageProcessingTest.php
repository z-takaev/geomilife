<?php

declare(strict_types=1);

use App\Actions\Images\ProcessImageAction;
use App\Filament\Forms\Components\SpatieMediaLibrary;
use App\Filament\Resources\Products\Schemas\ProductForm;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

it('обрезает изображения товара до квадрата без полей', function (string $field): void {
    $livewire = new class extends Component implements HasSchemas
    {
        use InteractsWithSchemas;
    };
    $schema = ProductForm::configure(Schema::make($livewire));
    $tabs = collect($schema->getComponents(withHidden: true))
        ->sole(static fn (object $component): bool => $component instanceof Tabs);
    $mainTab = collect($tabs->getChildSchema()->getComponents(withHidden: true))
        ->first(static fn (object $component): bool => $component instanceof Tab);
    $component = $mainTab->getChildSchema()->getFlatComponents(withHidden: true)[$field];

    expect($component)->toBeInstanceOf(SpatieMediaLibrary::class);

    $imageProcessing = (new ReflectionProperty($component, 'imageProcessing'))->getValue($component);
    $source = new Imagick;
    $source->newImage(1600, 800, new ImagickPixel('blue'), 'png');
    $result = app(ProcessImageAction::class)->run($source->getImageBlob(), $imageProcessing);
    $image = new Imagick;
    $image->readImageBlob($result);

    $cornerColor = $image->getImagePixelColor(0, 0)->getColor();

    expect($image->getImageWidth())->toBe(1200)
        ->and($image->getImageHeight())->toBe(1200)
        ->and($cornerColor['r'])->toBeLessThan(50)
        ->and($cornerColor['g'])->toBeLessThan(50)
        ->and($cornerColor['b'])->toBeGreaterThan(200);

    $source->clear();
    $image->clear();
})->with(['image', 'gallery']);
