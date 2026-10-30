<div {{ $attributes->class(['modal', 'modalCentered', 'fade']) }}>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="icon-close-popup" data-bs-dismiss="modal">
                <i class="icon icon-close"></i>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
