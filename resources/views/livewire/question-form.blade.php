<form class="form-get faq-question-form">
    <h2 class="form-title h5 text-white text-center">
        {{ __('Задать вопрос') }}
    </h2>

    <div class="form-content">
        <fieldset class="tf-field">
            <label for="question-name" class="tf-lable cl-text-main">
                {{ __('Имя') }}<span class="text-primary">*</span>
            </label>
            <input type="text" id="question-name" placeholder="{{ __('Введите ваше имя') }}" autocomplete="name">
        </fieldset>

        <fieldset class="tf-field">
            <label for="question-phone" class="tf-lable cl-text-main">
                {{ __('Контактный телефон') }}<span class="text-primary">*</span>
            </label>
            <input type="tel" id="question-phone" name="phone" placeholder="{{ __('+7 (___) ___-__-__') }}"
                autocomplete="tel" inputmode="tel" maxlength="18" data-phone-mask>
        </fieldset>

        <fieldset class="tf-field">
            <label for="question-message" class="tf-lable cl-text-main">
                {{ __('Ваш вопрос') }}<span class="text-primary">*</span>
            </label>
            <textarea id="question-message" placeholder="{{ __('Напишите ваш вопрос') }}"></textarea>
        </fieldset>
    </div>

    <div class="btn-wrap">
        <button type="button" class="tf-btn-text-icon fw-semibold animate-btn animate-dark">
            {{ __('Отправить вопрос') }}
            <span class="ic-wrap" aria-hidden="true">
                <i class="icon icon-ArrowUpRight"></i>
            </span>
        </button>
    </div>
</form>
