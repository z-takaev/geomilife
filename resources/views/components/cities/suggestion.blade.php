@props(['city'])

<span>{{ $city->name }}</span>
@if (filled($city->region))
    <small>{{ $city->region }}</small>
@endif
