<!-- Mobile Menu -->
<div class="offcanvas offcanvas-start canvas-mb" id="mobileMenu">
    <div class="canvas-header">
        <span class="icon-close-popup" data-bs-dismiss="offcanvas">
            <i class="icon icon-close"></i>
        </span>
    </div>
    <div class="canvas-body">
        <div class="mb-content-top">
            <ul class="nav-ul-mb" id="wrapper-menu-navigation"></ul>
        </div>
        <div class="need-help-wrap">
            <h6 class="nd-title text-caption-02 text-primary mb-12">{{ __('КОНТАКТЫ') }}</h6>
            <a href="#" class="text-caption-01 text-primary mb-8">
                {{ __(settings()?->address) }}
            </a>
            <a href="tel:{{ preg_replace('/[^\d+]/', '', settings()?->phone) }}"
                class="h5 fw-medium text-primary mb-12">
                {{ __(settings()?->phone) }}
            </a>
            <a href="mailto:{{ settings()?->email }}" class="text-caption-01 text-primary mb-20">
                {{ __(settings()?->email) }}
            </a>
            <ul class="social-list d-flex align-items-center gap-24">
                @if (settings()?->instagram_url)
                    <li>
                        <a href="{{ settings()?->instagram_url }}" class="d-flex text-primary"
                            aria-label="{{ __('Инстаграм') }}" target="_blank" rel="noopener noreferrer">
                            <i class="social-icon social-icon--instagram" aria-hidden="true"></i>
                        </a>
                    </li>
                @endif
                @if (settings()?->youtube_url)
                    <li>
                        <a href="{{ settings()?->youtube_url }}" class="d-flex text-primary"
                            aria-label="{{ __('Ютуб') }}" target="_blank" rel="noopener noreferrer">
                            <i class="social-icon social-icon--youtube" aria-hidden="true"></i>
                        </a>
                    </li>
                @endif
                @if (settings()?->vk_url)
                    <li>
                        <a href="{{ settings()?->vk_url }}" class="d-flex text-primary"
                            aria-label="{{ __('ВКонтакте') }}" target="_blank" rel="noopener noreferrer">
                            <i class="social-icon social-icon--vk" aria-hidden="true"></i>
                        </a>
                    </li>
                @endif
                @if (settings()?->telegram_url)
                    <li>
                        <a href="{{ settings()?->telegram_url }}" class="d-flex text-primary"
                            aria-label="{{ __('Телеграм') }}" target="_blank" rel="noopener noreferrer">
                            <i class="social-icon social-icon--telegram" aria-hidden="true"></i>
                        </a>
                    </li>
                @endif
                @if (settings()?->whatsapp_url)
                    <li>
                        <a href="{{ settings()?->whatsapp_url }}" class="d-flex text-primary"
                            aria-label="{{ __('Ватсап') }}" target="_blank" rel="noopener noreferrer">
                            <i class="social-icon social-icon--whatsapp" aria-hidden="true"></i>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</div>
<!-- /Mobile Menu -->
