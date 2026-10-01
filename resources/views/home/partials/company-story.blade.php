<!-- Company Story -->
<section id="company-story" class="home-content-section home-story">
    <div class="container">
        <div class="home-story-card wow fadeInUp">
            <div class="home-story-content">
                <p class="home-story-eyebrow">Наша история</p>
                <h2 class="home-story-title">Из сердца Осетии — с заботой о вашем здоровье</h2>
                <p class="home-story-text">
                    GeoMiLife выросла из простой идеи: создавать натуральные продукты, в составе которых мы уверены
                    сами. Мы бережно отбираем сырьё, производим масла холодного отжима небольшими партиями и
                    контролируем каждый этап — от семени до готовой бутылки.
                </p>
                <p class="home-story-text">
                    Посмотрите короткое видео о людях, принципах и месте, с которых начинается каждый наш продукт.
                </p>
                <button type="button" class="tf-btn home-story-button" data-bs-toggle="modal"
                    data-bs-target="#companyStoryVideo">
                    <i class="icon icon-Play" aria-hidden="true"></i>
                    Смотреть нашу историю
                </button>
            </div>

            <div class="about-video home-story-media">
                <div class="video-thumb">
                    <img loading="lazy" width="1433" height="840"
                        src="{{ asset('assets/images/section/company-story.jpg') }}"
                        alt="Бережное выращивание натуральных продуктов">
                </div>
                <div class="home-story-location">
                    <i class="icon icon-MapPin" aria-hidden="true"></i>
                    Северная Осетия
                </div>
                <button type="button" class="btn-view-video" data-bs-toggle="modal"
                    data-bs-target="#companyStoryVideo" aria-label="Смотреть видео о GeoMiLife">
                    <i class="icon icon-Play" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</section>

<div class="modal fade home-video-modal" id="companyStoryVideo" tabindex="-1"
    aria-labelledby="companyStoryVideoTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="home-video-modal-header">
                <h2 class="home-video-modal-title" id="companyStoryVideoTitle">История GeoMiLife</h2>
                <button type="button" class="home-video-modal-close" data-bs-dismiss="modal" aria-label="Закрыть">
                    <i class="icon icon-close" aria-hidden="true"></i>
                </button>
            </div>
            <div class="ratio ratio-16x9">
                <iframe title="История GeoMiLife на VK Видео"
                    data-src="https://vkvideo.ru/video_ext.php?oid=-139157852&amp;id=456239750&amp;hash=d79c769f2d5d3f53&amp;hd=3"
                    allow="autoplay; encrypted-media; fullscreen; picture-in-picture; screen-wake-lock"
                    allowfullscreen></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const videoModal = document.getElementById('companyStoryVideo');
        const videoFrame = videoModal?.querySelector('iframe');

        if (!videoModal || !videoFrame) {
            return;
        }

        videoModal.addEventListener('show.bs.modal', () => {
            videoFrame.src = videoFrame.dataset.src;
        });

        videoModal.addEventListener('hidden.bs.modal', () => {
            videoFrame.removeAttribute('src');
        });
    })();
</script>
<!-- /Company Story -->
