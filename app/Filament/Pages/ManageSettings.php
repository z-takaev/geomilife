<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Forms\Components\SpatieMediaLibrary;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;

/**
 * @property-read Schema $form
 */
final class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Конфигурация сайта';

    protected static ?string $title = 'Конфигурация сайта';

    protected string $view = 'filament.pages.manage-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    private ?Setting $setting = null;

    public static function canAccess(): bool
    {
        return auth('filament')->user()?->role?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(self::canAccess(), 403);

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
                                        ->required()
                                        ->regex('/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/'),
                                    TextInput::make('email')
                                        ->label('Email')
                                        ->email()
                                        ->required()
                                        ->maxLength(50),
                                    TextInput::make('admin_email')
                                        ->label('Email администратора')
                                        ->helperText('На этот адрес будут приходить письма с сайта.')
                                        ->email()
                                        ->required()
                                        ->maxLength(50),

                                    Textarea::make('address')
                                        ->label('Адрес')
                                        ->required()
                                        ->maxLength(100)
                                        ->rows(2)
                                        ->columnSpanFull(),
                                    Textarea::make('working_hours')
                                        ->label('Режим работы')
                                        ->required()
                                        ->maxLength(100)
                                        ->rows(2)
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
                                        ->label('ВКонтакте')
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

                                    TextInput::make('yandex_map_id')
                                        ->label('Яндекс Карта')
                                        ->helperText('Укажите идентификатор после constructor: в ссылке из конструктора Яндекс Карт.')
                                        ->maxLength(64)
                                        ->regex('/\A[a-f0-9]{64}\z/')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),

                            Tab::make('Преимущества')
                                ->schema([
                                    Repeater::make('benefits')
                                        ->label('Преимущества на главной странице')
                                        ->schema([
                                            TextInput::make('title')
                                                ->label('Заголовок')
                                                ->required()
                                                ->maxLength(50),
                                            Textarea::make('description')
                                                ->label('Описание')
                                                ->required()
                                                ->maxLength(100)
                                                ->rows(2),
                                        ])
                                        ->itemLabel(static fn (array $state): string => $state['title'])
                                        ->addable(false)
                                        ->deletable(false)
                                        ->reorderable(false)
                                        ->collapsible()
                                        ->collapsed()
                                        ->required()
                                        ->minItems(5)
                                        ->maxItems(5),
                                ]),

                            Tab::make('Наша история')
                                ->schema([
                                    TextInput::make('story_title')
                                        ->label('Заголовок')
                                        ->maxLength(100),
                                    TextInput::make('story_video_url')
                                        ->label('Ссылка на видео')
                                        ->helperText('Прямая ссылка на VK Видео')
                                        ->url()
                                        ->maxLength(2048),
                                    Textarea::make('story_description')
                                        ->label('Описание')
                                        ->maxLength(500)
                                        ->rows(6)
                                        ->columnSpanFull(),
                                    SpatieMediaLibrary::make('story_image')
                                        ->label('Изображение')
                                        ->collection('story_image')
                                        ->maxFiles(1)
                                        ->maxSize(5120)
                                        ->processImage(
                                            static fn (Image $image): Image => $image->fit(Fit::Crop, 1800, 1200),
                                        )
                                        ->helperText('Рекомендуемый размер: 1800×1200 пикселей.'),
                                ])
                                ->columns(2),

                            Tab::make('Баннеры')
                                ->schema([
                                    Section::make('Широкий баннер')
                                        ->schema([
                                            SpatieMediaLibrary::make('banner_wide_image')
                                                ->label('Для компьютеров')
                                                ->collection('banner_wide_image')
                                                ->maxFiles(1)
                                                ->maxSize(5120)
                                                ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 1290, 200))
                                                ->helperText('Рекомендуемый размер: 1290×200 пикселей.'),
                                            SpatieMediaLibrary::make('banner_wide_mobile_image')
                                                ->label('Для мобильных устройств')
                                                ->collection('banner_wide_mobile_image')
                                                ->maxFiles(1)
                                                ->maxSize(5120)
                                                ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 750, 360))
                                                ->helperText('Рекомендуемый размер: 750×360 пикселей.'),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->collapsed(),
                                    Section::make('Двойной баннер')
                                        ->schema([
                                            Fieldset::make('Первый баннер')
                                                ->schema([
                                                    SpatieMediaLibrary::make('banner_double_first_image')
                                                        ->label('Для компьютеров')
                                                        ->collection('banner_double_first_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 635, 180))
                                                        ->helperText('Рекомендуемый размер: 635×180 пикселей.'),
                                                    SpatieMediaLibrary::make('banner_double_first_mobile_image')
                                                        ->label('Для мобильных устройств')
                                                        ->collection('banner_double_first_mobile_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 750, 360))
                                                        ->helperText('Рекомендуемый размер: 750×360 пикселей.'),
                                                ]),
                                            Fieldset::make('Второй баннер')
                                                ->schema([
                                                    SpatieMediaLibrary::make('banner_double_second_image')
                                                        ->label('Для компьютеров')
                                                        ->collection('banner_double_second_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 635, 180))
                                                        ->helperText('Рекомендуемый размер: 635×180 пикселей.'),
                                                    SpatieMediaLibrary::make('banner_double_second_mobile_image')
                                                        ->label('Для мобильных устройств')
                                                        ->collection('banner_double_second_mobile_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 750, 360))
                                                        ->helperText('Рекомендуемый размер: 750×360 пикселей.'),
                                                ]),
                                        ])
                                        ->collapsible()
                                        ->collapsed(),
                                    Section::make('Тройной баннер')
                                        ->schema([
                                            Fieldset::make('Первый баннер')
                                                ->schema([
                                                    SpatieMediaLibrary::make('banner_triple_first_image')
                                                        ->label('Для компьютеров')
                                                        ->collection('banner_triple_first_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 417, 200))
                                                        ->helperText('Рекомендуемый размер: 417×200 пикселей.'),
                                                    SpatieMediaLibrary::make('banner_triple_first_mobile_image')
                                                        ->label('Для мобильных устройств')
                                                        ->collection('banner_triple_first_mobile_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 750, 360))
                                                        ->helperText('Рекомендуемый размер: 750×360 пикселей.'),
                                                ]),
                                            Fieldset::make('Второй баннер')
                                                ->schema([
                                                    SpatieMediaLibrary::make('banner_triple_second_image')
                                                        ->label('Для компьютеров')
                                                        ->collection('banner_triple_second_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 417, 200))
                                                        ->helperText('Рекомендуемый размер: 417×200 пикселей.'),
                                                    SpatieMediaLibrary::make('banner_triple_second_mobile_image')
                                                        ->label('Для мобильных устройств')
                                                        ->collection('banner_triple_second_mobile_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 750, 360))
                                                        ->helperText('Рекомендуемый размер: 750×360 пикселей.'),
                                                ]),
                                            Fieldset::make('Третий баннер')
                                                ->schema([
                                                    SpatieMediaLibrary::make('banner_triple_third_image')
                                                        ->label('Для компьютеров')
                                                        ->collection('banner_triple_third_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 417, 200))
                                                        ->helperText('Рекомендуемый размер: 417×200 пикселей.'),
                                                    SpatieMediaLibrary::make('banner_triple_third_mobile_image')
                                                        ->label('Для мобильных устройств')
                                                        ->collection('banner_triple_third_mobile_image')
                                                        ->maxFiles(1)
                                                        ->maxSize(5120)
                                                        ->processImage(static fn (Image $image): Image => $image->fit(Fit::Crop, 750, 360))
                                                        ->helperText('Рекомендуемый размер: 750×360 пикселей.'),
                                                ]),
                                        ])
                                        ->collapsible()
                                        ->collapsed(),
                                ]),

                            Tab::make('Общее')
                                ->schema([
                                    FileUpload::make('privacy_policy')
                                        ->label('Политика обработки персональных данных')
                                        ->disk('public')
                                        ->directory('documents/privacy-policy')
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->maxSize(10240)
                                        ->required()
                                        ->previewable(false)
                                        ->preventFilePathTampering()
                                        ->openable()
                                        ->downloadable()
                                        ->helperText('Обязательный PDF-документ до 10 МБ.')
                                        ->columnSpanFull(),
                                    TextInput::make('footer_title')
                                        ->label('Заголовок футера')
                                        ->maxLength(50),
                                    Textarea::make('footer_description')
                                        ->label('Описание футера')
                                        ->maxLength(100)
                                        ->rows(3)
                                        ->columnSpanFull(),
                                    Toggle::make('snow_enabled')
                                        ->label('Режим снега')
                                        ->helperText('Включает снег в публичной части сайта.'),
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
        $previousDocument = $setting->privacy_policy;
        $setting->fill($this->form->getState());
        $setting->save();

        if ($previousDocument && $previousDocument !== $setting->privacy_policy) {
            Storage::disk('public')->delete($previousDocument);
        }

        $this->form->fill($setting->attributesToArray());

        Notification::make()
            ->success()
            ->title('Конфигурация сайта сохранена')
            ->send();
    }

    private function getRecord(): Setting
    {
        return $this->setting ??= Setting::query()->sole();
    }
}
