<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ShippingBox;
use Illuminate\Database\Seeder;

final class ShippingBoxSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [230, 120, 120, 120],
            [260, 75, 70, 80],
            [90, 85, 105, 60],
            [165, 115, 245, 150],
            [275, 215, 175, 220],
            [310, 210, 310, 300],
        ] as [$length, $width, $height, $weight]) {
            ShippingBox::query()
                ->firstOrCreate(
                    ['name' => "Коробка {$length} × {$width} × {$height}"],
                    [
                        'length' => $length,
                        'width' => $width,
                        'height' => $height,
                        'weight' => $weight,
                        'is_active' => true,
                    ]
                );
        }
    }
}
