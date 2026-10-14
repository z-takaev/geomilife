<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Images\ProcessImageAction;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

final class SettingSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private const array IMAGES = [
        'story_image' => 'company-story.jpg',
        'banner_wide_image' => 'promo-natural-ossetia.jpg',
        'banner_double_first_image' => 'promo-cold-pressed-oils.jpg',
        'banner_double_second_image' => 'promo-urbech.jpg',
        'banner_triple_first_image' => 'promo-honey-landscape.webp',
        'banner_triple_second_image' => 'promo-healthy-sweets-landscape.webp',
        'banner_triple_third_image' => 'promo-daily-health-landscape.webp',
    ];

    public function run(): void
    {
        $setting = Setting::query()->updateOrCreate([], [
            'phone' => '+7-9888-731-020',
            'email' => 'geomilife@bk.ru',
            'address' => 'ул. Тамаева 35, Владикавказ',
            'working_hours' => 'Пн–Пт: 09:00–18:00, Сб–Вс: выходной',
            'instagram_url' => 'https://www.instagram.com/geomilife/',
            'youtube_url' => 'https://www.youtube.com/@geomilife8532',
            'vk_url' => 'https://vk.ru/geomilifee',
            'telegram_url' => 'https://t.me/+79888731020',
            'whatsapp_url' => 'https://wa.me/79888731020',
            'footer_title' => 'Натуральные продукты GeoMiLife',
            'footer_description' => 'Сыродавленные масла, урбеч и полезные продукты собственного производства с доставкой по России.',
            'story_title' => 'Из сердца Осетии — с заботой о вашем здоровье',
            'story_description' => "GeoMiLife выросла из простой идеи: создавать натуральные продукты, в составе которых мы уверены сами. Мы бережно отбираем сырьё, производим масла холодного отжима небольшими партиями и контролируем каждый этап — от семени до готовой бутылки.\n\nПосмотрите короткое видео о людях, принципах и месте, с которых начинается каждый наш продукт.",
            'story_video_url' => 'https://vkvideo.ru/video_ext.php?oid=-139157852&id=456239750&hash=d79c769f2d5d3f53&hd=3&autoplay=1&js_api=1',
        ]);

        foreach (self::IMAGES as $collection => $fileName) {
            $this->addImage($setting, $collection, $fileName);
        }
    }

    private function addImage(Setting $setting, string $collection, string $fileName): void
    {
        $contents = app(ProcessImageAction::class)->run(
            File::get(base_path('tests/Fixtures/images/Setting/'.$fileName)),
        );

        $setting->clearMediaCollection($collection);
        $setting->addMediaFromString($contents)
            ->usingName(pathinfo($fileName, PATHINFO_FILENAME))
            ->usingFileName(pathinfo($fileName, PATHINFO_FILENAME).'.webp')
            ->toMediaCollection($collection);
    }
}
