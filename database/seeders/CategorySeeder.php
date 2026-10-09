<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Images\ProcessImageAction;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Масла', 'image' => 'oils.png'],
            ['name' => 'Мёд и продукты пчеловодства', 'image' => 'honey-products.png'],
            ['name' => 'Жмых / мука', 'image' => 'oil-cake-flour.png'],
            ['name' => 'Семена', 'image' => 'seeds.png'],
            ['name' => 'Урбеч', 'image' => 'urbech.png'],
            ['name' => 'БАД', 'image' => 'supplements.png'],
            ['name' => 'Про- и Пребиотики', 'image' => 'pro-prebiotics.png'],
            ['name' => 'Грибы', 'image' => 'mushrooms.png'],
            ['name' => 'Специи', 'image' => 'spices.png'],
            ['name' => 'Полезные сладости', 'image' => 'healthy-sweets.png'],
            ['name' => 'Бакалея', 'image' => 'groceries.png'],
            ['name' => 'Средства', 'image' => 'care-products.png'],
        ];

        foreach ($categories as $index => $data) {
            $category = Category::query()->firstOrCreate(
                ['slug' => Str::slug($data['name'])],
                ['name' => $data['name'], 'order' => $index + 1, 'is_active' => true],
            );

            if ($category->hasMedia('image')) {
                continue;
            }

            $contents = app(ProcessImageAction::class)->run(
                File::get(base_path('tests/Fixtures/images/Category/'.$data['image'])),
                static fn (Image $image): Image => $image->fit(Fit::Fill, 120, 120),
            );

            $category->addMediaFromString($contents)
                ->usingFileName(pathinfo($data['image'], PATHINFO_FILENAME).'.webp')
                ->usingName($data['name'])
                ->toMediaCollection('image');
        }
    }
}
