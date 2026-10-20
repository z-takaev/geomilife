<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Forms\Components\MediaImageUpload;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

/**
 * @property-read Schema $form
 */
final class ManageSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Конфигурация сайта';

    protected static ?string $title = 'Конфигурация сайта';

    protected string $view = 'filament.pages.manage-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    private ?Setting $setting = null;

    public function mount(): void
    {
        $this->form->fill($this->getRecord()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Tabs::make('Конфигурация сайта')
                        ->tabs([
                            Tab::make('Контактная информация')
                                ->schema([
                                    TextInput::make('phone')
                                        ->label('Телефон')
                                        ->tel()
                                        ->mask('+7 (999) 999-99-99')
                                        ->placeholder('+7 (988) 375-75-55')
                                        ->required()
                                        ->regex('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/'),
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->required()
                                        ->maxLength(255),
                                    Textarea::make('address')
                                        ->label('Адрес')
                                        ->required()
                                        ->maxLength(1000)
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    Textarea::make('working_hours')
                                        ->label('Режим работы')
                                        ->required()
                                        ->maxLength(1000)
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),
                            Tab::make('Футер')
                                ->schema([
                                    TextInput::make('footer_title')
                                        ->label('Заголовок')
                                        ->maxLength(255),
                                    Textarea::make('footer_description')
                                        ->label('Описание')
                                        ->maxLength(2000)
                                        ->rows(4)
                                        ->columnSpanFull(),
                                    TextInput::make('instagram_url')
                                        ->label('Instagram')
                                        ->url()
                                        ->maxLength(2048),
                                    TextInput::make('youtube_url')
                                        ->label('YouTube')
                                        ->url()
                                        ->maxLength(2048),
                                    TextInput::make('vk_url')
                                        ->label('VK')
                                        ->url()
                                        ->maxLength(2048),
                                    TextInput::make('telegram_url')
                                        ->label('Telegram')
                                        ->url()
                                        ->maxLength(2048),
                                    TextInput::make('whatsapp_url')
                                        ->label('WhatsApp')
                                        ->url()
                                        ->maxLength(2048),
                                ])
                                ->columns(2),
                            Tab::make('Наша история')
                                ->schema([
                                    TextInput::make('story_title')
                                        ->label('Заголовок')
                                        ->maxLength(255),
                                    TextInput::make('story_video_url')
                                        ->label('Ссылка на видео')
                                        ->helperText('Можно указать прямую ссылку VK Видео, например: https://vkvideo.ru/video-130698013_456241587')
                                        ->url()
                                        ->maxLength(2048),
                                    Textarea::make('story_description')
                                        ->label('Описание')
                                        ->maxLength(5000)
                                        ->rows(6)
                                        ->columnSpanFull(),
                                    self::imageUpload('story_image', 'Изображение', columnSpanFull: true),
                                ])
                                ->columns(2),
                            Tab::make('Баннеры')
                                ->schema([
                                    self::bannerUpload('banner_wide_image', 'Широкий баннер', 1290, 200, columnSpanFull: true),
                                    self::bannerUpload('banner_wide_mobile_image', 'Широкий баннер — мобильное изображение', 750, 360, columnSpanFull: true),
                                    self::bannerUpload('banner_double_first_image', 'Двойной баннер — первое изображение', 635, 180),
                                    self::bannerUpload('banner_double_first_mobile_image', 'Двойной баннер — первое мобильное изображение', 750, 360),
                                    self::bannerUpload('banner_double_second_image', 'Двойной баннер — второе изображение', 635, 180),
                                    self::bannerUpload('banner_double_second_mobile_image', 'Двойной баннер — второе мобильное изображение', 750, 360),
                                    self::bannerUpload('banner_triple_first_image', 'Тройной баннер — первое изображение', 417, 200),
                                    self::bannerUpload('banner_triple_first_mobile_image', 'Тройной баннер — первое мобильное изображение', 750, 360),
                                    self::bannerUpload('banner_triple_second_image', 'Тройной баннер — второе изображение', 417, 200),
                                    self::bannerUpload('banner_triple_second_mobile_image', 'Тройной баннер — второе мобильное изображение', 750, 360),
                                    self::bannerUpload('banner_triple_third_image', 'Тройной баннер — третье изображение', 417, 200),
                                    self::bannerUpload('banner_triple_third_mobile_image', 'Тройной баннер — третье мобильное изображение', 750, 360),
                                ])
                                ->columns(2),
                        ])
                        ->persistTab()
                        ->id('site-configuration-tabs')
                        ->columnSpanFull(),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Сохранить')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $setting = $this->getRecord();
        $setting->fill($this->form->getState());
        $setting->save();

        $this->form->record($setting)->saveRelationships();

        Notification::make()
            ->success()
            ->title('Конфигурация сайта сохранена')
            ->send();
    }

    private static function imageUpload(
        string $collection,
        string $label,
        ?string $recommendation = null,
        bool $columnSpanFull = false,
    ): MediaImageUpload {
        $helperText = 'Статичное JPEG, PNG или WebP до 5 МБ.';

        if ($recommendation !== null) {
            $helperText .= ' '.$recommendation;
        }

        $upload = MediaImageUpload::make($collection)
            ->label($label)
            ->collection($collection)
            ->maxFiles(1)
            ->maxSize(5120)
            ->processImage(static fn (Image $image): Image => $image)
            ->helperText($helperText);

        if ($columnSpanFull) {
            $upload->columnSpanFull();
        }

        return $upload;
    }

    private static function bannerUpload(
        string $collection,
        string $label,
        int $width,
        int $height,
        bool $columnSpanFull = false,
    ): MediaImageUpload {
        $upload = self::imageUpload(
            $collection,
            $label,
            "Результат: {$width} × {$height}. Изображение будет обрезано по центру. Исходник не сохраняется.",
            $columnSpanFull,
        );

        return $upload->processImage(
            static fn (Image $image): Image => $image->fit(Fit::Crop, $width, $height),
        );
    }

    private function getRecord(): Setting
    {
        return $this->setting ??= Setting::query()->firstOrNew();
    }
}
