<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Repositories\Categories\CategoryRepository;
use App\Repositories\Products\ProductRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

final class SearchSuggestions extends Component
{
    public string $query = '';

    public function submit(): void
    {
        $query = mb_substr(trim($this->query), 0, 255);

        if ($query === '') {
            return;
        }

        $this->redirectRoute('search.index', ['q' => $query]);
    }

    public function render(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository,
    ): View {
        $query = mb_substr(trim($this->query), 0, 255);
        $categories = new Collection;
        $products = new Collection;

        if ($query !== '') {
            $categories = $categoryRepository->searchSuggestions($query);
            $products = $productRepository->searchSuggestions($query);
        }

        return view('livewire.search-suggestions', compact('query', 'categories', 'products'));
    }
}
