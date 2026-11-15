<ol class="order-processing-stages" aria-label="{{ __('Этапы обработки заказа') }}">
    @foreach ($stages as $stage)
        <li class="order-processing-stages__item" @if ($stage['current']) aria-current="step" @endif>
            <x-filament::badge :color="$stage['color']">
                {{ __($stage['label']) }}
                @if ($stage['current'])
                    — {{ __('Текущий этап') }}
                @endif
            </x-filament::badge>
            @unless ($loop->last)
                <span class="order-processing-stages__arrow" aria-hidden="true">→</span>
            @endunless
        </li>
    @endforeach
</ol>
