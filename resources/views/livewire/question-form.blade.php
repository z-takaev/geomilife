<div>
    <form class="form-get faq-question-form" wire:submit="submit">
        <h2 class="form-title h5 text-white text-center">
            {{ __('Задать вопрос') }}
        </h2>

        <div class="form-content">
            <fieldset class="tf-field">
                <label for="question-name" class="tf-lable cl-text-main">
                    {{ __('Имя') }}<span class="text-primary">*</span>
                </label>
                <input type="text" id="question-name" name="name" wire:model="name"
                    placeholder="{{ __('Введите ваше имя') }}" autocomplete="name" maxlength="255" required
                    @class(['is-invalid' => $errors->has('name')])>
                @error('name')
                    <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="tf-field">
                <label for="question-phone" class="tf-lable cl-text-main">
                    {{ __('Контактный телефон') }}<span class="text-primary">*</span>
                </label>
                <input type="tel" id="question-phone" name="phone" wire:model="phone"
                    placeholder="{{ __('+7 (___) ___-__-__') }}" autocomplete="tel" inputmode="tel" maxlength="18"
                    data-phone-mask required @class(['is-invalid' => $errors->has('phone')])>
                @error('phone')
                    <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
            </fieldset>

            <fieldset class="tf-field">
                <label for="question-message" class="tf-lable cl-text-main">
                    {{ __('Ваш вопрос') }}<span class="text-primary">*</span>
                </label>
                <textarea id="question-message" name="message" wire:model="message" placeholder="{{ __('Напишите ваш вопрос') }}"
                    maxlength="5000" required @class(['is-invalid' => $errors->has('message')])></textarea>
                @error('message')
                    <span class="invalid-feedback d-block">{{ $message }}</span>
                @enderror
            </fieldset>
        </div>

        <div class="btn-wrap">
            <button type="submit" class="tf-btn-text-icon fw-semibold animate-btn animate-dark"
                wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">{{ __('Отправить вопрос') }}</span>
                <span wire:loading wire:target="submit">{{ __('Отправка...') }}</span>
                <span class="ic-wrap" aria-hidden="true">
                    <i class="icon icon-ArrowUpRight"></i>
                </span>
            </button>
        </div>
    </form>

    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div class="toast question-form-toast" role="status" aria-live="polite" aria-atomic="true" wire:ignore>
            <div class="d-flex align-items-center">
                <div class="toast-body">
                    {{ __('Ваш вопрос отправлен.') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="{{ __('Закрыть уведомление') }}"></button>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        $wire.on('question-sent', () => {
            const toastElement = $wire.$el.querySelector('.question-form-toast');

            bootstrap.Toast.getOrCreateInstance(toastElement).show();
        });
    </script>
@endscript
