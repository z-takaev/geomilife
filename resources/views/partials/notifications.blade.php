<div class="toast-container position-fixed top-0 end-0 p-3" x-data="{ message: '', type: 'success' }"
    x-on:notify.window="
        if (typeof $event.detail.message !== 'string' || !$event.detail.message.trim()) return;
        message = $event.detail.message;
        type = ['success', 'error', 'warning'].includes($event.detail.type) ? $event.detail.type : 'success';
        $nextTick(() => bootstrap.Toast.getOrCreateInstance($refs.toast).show());
    ">
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
