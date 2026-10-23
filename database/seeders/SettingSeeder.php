<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\Media\FixtureImages;
use Illuminate\Database\Seeder;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

final class SettingSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const array IMAGES = [
        'story_image' => 'company-story.jpg',
        'banner_wide_image' => 'promo-natural-ossetia.jpg',
        'banner_wide_mobile_image' => 'promo-natural-ossetia-mobile.jpg',
        'banner_double_first_image' => 'promo-cold-pressed-oils.jpg',
        'banner_double_first_mobile_image' => 'promo-cold-pressed-oils-mobile.jpg',
        'banner_double_second_image' => 'promo-urbech.jpg',
        'banner_double_second_mobile_image' => 'promo-urbech-mobile.jpg',
        'banner_triple_first_image' => 'promo-honey-landscape.webp',
        'banner_triple_first_mobile_image' => 'promo-honey-mobile.jpg',
        'banner_triple_second_image' => 'promo-healthy-sweets-landscape.webp',
        'banner_triple_second_mobile_image' => 'promo-healthy-sweets-mobile.jpg',
        'banner_triple_third_image' => 'promo-daily-health-landscape.webp',
        'banner_triple_third_mobile_image' => 'promo-daily-health-mobile.jpg',
    ];

    public function run(): void
    {
        $setting = Setting::query()->updateOrCreate([], [
            'phone' => '+7 (988) 873-10-20',
            'email' => 'geomilife@bk.ru',
            'address' => 'ул. Тамаева 35, Владикавказ',
            'working_hours' => 'Пн–Пт: 09:00–18:00, Сб–Вс: выходной',
            'yandex_map_script' => <<<'HTML'
                <iframe src="https://yandex.ru/map-widget/v1/?ll=44.681900%2C43.036700&z=14&pt=44.681900%2C43.036700%2Cpm2rdm" title="Карта с расположением магазина GeoMiLife" loading="lazy" allowfullscreen></iframe>
                HTML,
            'instagram_url' => 'https://www.instagram.com/geomilife/',
            'youtube_url' => 'https://www.youtube.com/@geomilife8532',
            'vk_url' => 'https://vk.ru/geomilifee',
            'telegram_url' => 'https://t.me/+79888731020',
            'whatsapp_url' => 'https://wa.me/79888731020',
            'footer_title' => 'Натуральные продукты GeoMiLife',
            'footer_description' => 'Сыродавленные масла, урбеч и полезные продукты собственного производства с доставкой по России.',
            'story_title' => 'Из сердца Осетии — с заботой о вашем здоровье',
            'story_description' => "GeoMiLife выросла из простой идеи: создавать натуральные продукты, в составе которых мы уверены сами. Мы бережно отбираем сырьё, производим масла холодного отжима небольшими партиями и контролируем каждый этап — от семени до готовой бутылки.\n\nПосмотрите короткое видео о людях, принципах и месте, с которых начинается каждый наш продукт.",
            'story_video_url' => 'https://vkvideo.ru/video-130698013_456241587',
        ]);

        foreach (self::IMAGES as $collection => $fileName) {
            $this->addImage($setting, $collection, $fileName);
        }
    }

    private function addImage(Setting $setting, string $collection, string $fileName): void
    {
        app(FixtureImages::class)->attach(
            model: $setting,
            file: 'Setting/'.$fileName,
            collection: $collection,
            processImage: $collection === 'story_image'
                ? static fn (Image $image): Image => $image->fit(Fit::Crop, 1800, 1200)
                : null,
        );
    }
}
