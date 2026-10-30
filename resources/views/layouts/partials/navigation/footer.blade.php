<div class="inner-center">
    @foreach ($columns as $column)
        <div class="footer-col-block">
            <p class="footer-heading footer-heading-mobile text-caption-02">
                {{ __($column->label) }}
            </p>
            <div class="tf-collapse-content">
                <ul class="footer-menu-list">
                    @foreach ($column->children as $item)
                        <li><a href="{{ $item->url }}" class="link">{{ __($item->label) }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endforeach
</div>
