<nav class="box-navigation d-none d-xl-block">
    <ul class="box-nav-menu">
        @foreach ($items as $item)
            <li @class(['menu-item', 'menu-item-catalog' => $item->catalog])>
                <a href="{{ $item->url }}" @class(['item-link', 'catalog-link' => $item->catalog, 'lh-28'])>
                    <span class="text">
                        @if ($item->catalog)
                            <span class="catalog-menu-icon" aria-hidden="true">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        @endif
                        {{ __($item->label) }}
                        @if ($item->hasChildren() && !$item->catalog)
                            <i class="icon icon-caret-down"></i>
                        @endif
                    </span>
                </a>
                @if ($item->hasChildren())
                    <div class="sub-menu">
                        <ul class="sub-menu_list">
                            @foreach ($item->children as $child)
                                <li @class(['has-menu_lv2' => $child->hasChildren()])>
                                    <a href="{{ $child->url }}" class="sub-menu_link">
                                        @if ($child->hasChildren())
                                            <span class="text">{{ __($child->label) }}</span>
                                            <i class="icon icon-caret-right"></i>
                                        @else
                                            {{ __($child->label) }}
                                        @endif
                                    </a>
                                    @if ($child->hasChildren())
                                        <ul class="sub-menu-lv2">
                                            @foreach ($child->children as $grandchild)
                                                <li>
                                                    <a href="{{ $grandchild->url }}" class="sub-menu_link">
                                                        {{ __($grandchild->label) }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
