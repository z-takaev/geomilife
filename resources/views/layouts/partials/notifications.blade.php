<div class="toast-container position-fixed top-0 end-0 p-3" x-data="notifications()"
    x-on:notify.window="show($event.detail)">
    <div class="toast app-toast" x-ref="toast" x-bind:class="'app-toast--' + type" role="status" aria-live="polite"
        aria-atomic="true">
        <div class="d-flex align-items-center">
            <div class="toast-body">
                <span x-text="message"></span>
            </div>
            <button type="button" class="btn-close me-2 m-auto"
                x-bind:class="{ 'btn-close-white': type !== 'warning' }" data-bs-dismiss="toast"
                aria-label="{{ __('Закрыть уведомление') }}"></button>
        </div>
    </div>
</div>
