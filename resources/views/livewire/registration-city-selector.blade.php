<div class="tf-field checkout-suggestions" x-data="checkoutSuggestions()" x-on:click.outside="open = false"
    x-on:keydown.escape.stop.prevent="close()">
    <label for="registration-city" class="tf-lable">{{ __('Город') }} <span class="text-secondary">*</span></label>
    <input type="hidden" name="city_id" value="{{ $selected_city_id }}">
    <input id="registration-city" type="search" wire:model.live.debounce.300ms="query"
        placeholder="{{ __('Начните вводить город') }}" autocomplete="off" maxlength="255" required x-ref="searchInput"
        x-on:focus="show()" x-on:input="show()">
    @if (mb_strlen(trim($query)) >= 3)
        <ul class="city-selection__results" x-show="open">
            @forelse ($cities as $city)
                <li wire:key="registration-city-{{ $city->id }}">
                    <button type="button" wire:click="select({{ $city->id }})" x-on:click="open = false">
                        <span>{{ $city->name }}</span>
                        @if ($city->region)
                            <small>{{ $city->region }}</small>
                        @endif
                    </button>
                </li>
            @empty
                <li class="city-selection__status">{{ __('Города не найдены') }}</li>
            @endforelse
        </ul>
    @endif
    @if ($errors->getBag('signUp')->has('city_id'))
        <span class="invalid-feedback d-block">{{ $errors->getBag('signUp')->first('city_id') }}</span>
    @endif
</div>
