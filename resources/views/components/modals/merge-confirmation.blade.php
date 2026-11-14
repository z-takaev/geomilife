@props(['id', 'title', 'description'])

<div
    {{ $attributes->class(['modal modalCentered fade modal-log merge-confirmation'])->merge([
        'id' => $id,
        'tabindex' => '-1',
        'aria-labelledby' => $id . 'Title',
    ]) }}>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="icon-close-popup" data-bs-dismiss="modal" aria-label="{{ __('Решить позже') }}">
                <i class="icon icon-close" aria-hidden="true"></i>
            </button>
            <div class="modal-body">
                <h3 class="title-pop text-black" id="{{ $id }}Title">{{ $title }}</h3>
                <p class="merge-confirmation__description">{{ $description }}</p>
                <div class="merge-confirmation__actions">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
