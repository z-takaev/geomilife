<div class="offcanvas offcanvas-top offcanvas-search" id="canvasSearch" wire:ignore.self>
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-8 offset-sm-2">
                <div class="offcanvas-body">
                    <form class="form-search" wire:submit="submit">
                        <fieldset>
                            <input type="search" placeholder="{{ __('Найти товар') }}" name="q"
                                wire:model.live.debounce.300ms="query" maxlength="255" required autocomplete="off">
                        </fieldset>
                        <button type="submit" class="link-2" aria-label="{{ __('Искать') }}">
                            <i class="icon icon-magnifying-glass"></i>
                        </button>
                    </form>

                    @if ($query !== '')
                        <div class="search-suggestions">
                            @if ($categories->isNotEmpty())
                                <div class="search-suggestions__group">
                                    <p class="search-suggestions__title">{{ __('Категории') }}</p>
                                    <ul class="search-suggestions__categories">
                                        @foreach ($categories as $category)
                                            <li wire:key="search-category-{{ $category->getKey() }}">
                                                <a href="{{ route('catalog.category', $category) }}"
                                                    class="search-suggestion">
                                                    <img src="{{ $category->getFirstMediaUrl('image') }}"
                                                        alt="{{ $category->name }}">
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
                                                    <img src="{{ $product->getFirstMediaUrl('image', 'card') }}"
                                                        alt="{{ $product->name }}">
                                                    <span>{{ $product->name }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($categories->isEmpty() && $products->isEmpty())
                                <p class="search-suggestions__empty">{{ __('Ничего не найдено') }}</p>
                            @endif
                        </div>
                    @endif
                </div>
                <span class="icon-close-popup icon-close" data-bs-dismiss="offcanvas"></span>
            </div>
        </div>
    </div>
</div>
