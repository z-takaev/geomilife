<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('сохраняет изображения media library в директории images', function (): void {
    Storage::fake(config('media-library.disk_name'));

    $category = Category::query()->create([
        'name' => 'Категория',
        'slug' => 'category',
        'is_active' => true,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->getKey(),
        'name' => 'Товар',
        'slug' => 'product',
        'is_active' => true,
    ]);

    $media = $product
        ->addMedia(UploadedFile::fake()->image('product.png'))
        ->toMediaCollection('image');

    expect($media->getPathRelativeToRoot())->toBe("images/{$media->getKey()}/product.png");
    Storage::disk(config('media-library.disk_name'))->assertExists($media->getPathRelativeToRoot());
});
