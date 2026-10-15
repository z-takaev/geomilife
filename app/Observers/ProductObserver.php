<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use App\Repositories\Categories\CategoryRepository;

final class ProductObserver
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function saved(Product $product): void
    {
        $this->categoryRepository->forget();
    }

    public function deleted(Product $product): void
    {
        $this->categoryRepository->forget();
    }
}
