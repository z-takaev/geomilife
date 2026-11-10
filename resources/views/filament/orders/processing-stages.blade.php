<ol class="flex flex-wrap items-center gap-3" aria-label="{{ __('Этапы обработки заказа') }}">
    @foreach ($stages as $stage)
        <li @if ($stage['current']) aria-current="step" @endif>
            <x-filament::badge :color="$stage['color']">
                {{ __($stage['label']) }}
                @if ($stage['current'])
                    — {{ __('Текущий этап') }}
                @endif
            </x-filament::badge>
        </li>
        @unless ($loop->last)
            <li aria-hidden="true">→</li>
        @endunless
    @endforeach
</ol>
