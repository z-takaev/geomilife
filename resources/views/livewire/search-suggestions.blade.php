<div class="offcanvas offcanvas-top offcanvas-search" id="canvasSearch" tabindex="-1"
    aria-label="{{ __('Поиск товаров') }}" wire:ignore.self>
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-8 offset-sm-2">
                <div class="offcanvas-body">
                    <form class="form-search" wire:submit="submit">
                        <fieldset>
                            <input type="search" placeholder="{{ __('Найти товар') }}" name="q"
                                aria-label="{{ __('Поисковый запрос') }}" wire:model.live.debounce.300ms="query"
                                maxlength="255" required autocomplete="off">
                        </fieldset>
                        <button type="submit" class="link-2" aria-label="{{ __('Искать') }}">
                            <i class="icon icon-magnifying-glass"></i>
                        </button>
                    </form>

                    <p wire:loading wire:target="query" role="status">{{ __('Поиск…') }}</p>
                    @if ($query !== '')
                        <div class="search-suggestions" wire:loading.remove wire:target="query" aria-live="polite">
                            @if ($categories->isNotEmpty())
                                <div class="search-suggestions__group">
                                    <p class="search-suggestions__title">{{ __('Категории') }}</p>
                                    <ul class="search-suggestions__categories">
                                        @foreach ($categories as $category)
                                            <li wire:key="search-category-{{ $category->getKey() }}">
                                                <a href="{{ route('catalog.category', $category) }}"
                                                    class="search-suggestion">
                                                    @if ($category->hasMedia('image'))
                                                        <img src="{{ $category->getFirstMediaUrl('image') }}"
                                                            alt="{{ $category->name }}">
                                                    @endif
                                                    <span>{{ $category->name }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($products->isNotEmpty())
                                <div class="search-suggestions__group">
                                    <p class="search-suggestions__title">{{ __('Товары') }}</p>
                                    <ul class="search-suggestions__products">
                                        @foreach ($products as $product)
                                            <li wire:key="search-product-{{ $product->getKey() }}">
                                                <a href="{{ route('products.show', $product) }}"
                                                    class="search-suggestion">
                                                    @if ($product->hasMedia('image'))
                                                        <img src="{{ $product->getFirstMediaUrl('image', 'card') }}"
                                                            alt="{{ $product->name }}">
                                                    @endif
                                                    <span>{{ $product->name }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <a href="{{ route('search', ['q' => $query]) }}"
                                class="link-underline text-primary">{{ __('Все результаты') }}</a>
                            @if ($categories->isEmpty() && $products->isEmpty())
                                <p class="search-suggestions__empty">{{ __('Ничего не найдено') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
                <button type="button" class="icon-close-popup icon-close" data-bs-dismiss="offcanvas"
                    aria-label="{{ __('Закрыть поиск') }}"></button>
            </div>
        </div>
    </div>
</div>
