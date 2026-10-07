<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class SettingSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $setting = Setting::query()->firstOrNew();

        $setting->fill([
            'phone' => $setting->phone ?? '+7-9888-731-020',
            'email' => $setting->email ?? 'geomilife@bk.ru',
            'address' => $setting->address ?? 'ул. Тамаева 35, Владикавказ',
            'footer_title' => $setting->footer_title ?? 'Натуральные продукты GeoMiLife',
            'footer_description' => $setting->footer_description
                ?? 'Сыродавленные масла, урбеч и полезные продукты собственного производства с доставкой по России.',
            'story_title' => $setting->story_title ?? 'Из сердца Осетии — с заботой о вашем здоровье',
            'story_description' => $setting->story_description
                ?? "GeoMiLife выросла из простой идеи: создавать натуральные продукты, в составе которых мы уверены сами. Мы бережно отбираем сырьё, производим масла холодного отжима небольшими партиями и контролируем каждый этап — от семени до готовой бутылки.\n\nПосмотрите короткое видео о людях, принципах и месте, с которых начинается каждый наш продукт.",
            'story_video_url' => $setting->story_video_url
                ?? 'https://vkvideo.ru/video_ext.php?oid=-139157852&id=456239750&hash=d79c769f2d5d3f53&hd=3&autoplay=1&js_api=1',
        ]);

        $setting->save();
    }
}
