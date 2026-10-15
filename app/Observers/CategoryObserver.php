<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use App\Repositories\Categories\CategoryRepository;

final class CategoryObserver
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function saved(Category $category): void
    {
        $this->categoryRepository->forget();
    }

    public function deleted(Category $category): void
    {
        $this->categoryRepository->forget();
    }
}
