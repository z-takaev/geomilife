<nav aria-label="{{ __('Навигация по страницам') }}">
    <ul {{ $attributes->class(['wg-pagination justify-content-center']) }}>
        <li><span class="pagination-item active" aria-current="page">1</span></li>
        <li><a href="#" class="pagination-item" aria-label="{{ __('Страница 2') }}">2</a></li>
        <li><a href="#" class="pagination-item" aria-label="{{ __('Страница 3') }}">3</a></li>
        <li>
            <a href="#" class="pagination-item" aria-label="{{ __('Следующая страница') }}">
                <i class="icon icon-caret-right" aria-hidden="true"></i>
            </a>
        </li>
    </ul>
</nav>
