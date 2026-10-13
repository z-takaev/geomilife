<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\SeoRoute;
use App\Models\SeoMetadata;
use Illuminate\Database\Seeder;

final class SeoMetadataSeeder extends Seeder
{
    public function run(): void
    {
        foreach (SeoRoute::cases() as $route) {
            SeoMetadata::query()->firstOrCreate(
                ['route_name' => $route->value],
                [
                    'title' => $route->title(),
                    'description' => $route->description(),
                ],
            );
        }
    }
}
