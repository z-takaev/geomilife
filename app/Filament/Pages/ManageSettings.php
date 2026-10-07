<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
final class ManageSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Настройки';

    protected static ?string $title = 'Настройки';

    protected string $view = 'filament.pages.manage-settings';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->getRecord()?->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Контактная информация')
                        ->schema([
                            TextInput::make('phone')
                                ->label('Телефон')
                                ->tel()
                                ->mask('+7-9999-999-999')
                                ->placeholder('+7-9888-731-020')
                                ->required()
                                ->regex('/^\+7-\d{4}-\d{3}-\d{3}$/'),
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
                        ])
                        ->columns(2),
                    Section::make('Футер')
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
                    Section::make('Наша история')
                        ->schema([
                            TextInput::make('story_title')
                                ->label('Заголовок')
                                ->maxLength(255),
                            TextInput::make('story_video_url')
                                ->label('Ссылка на видео')
                                ->url()
                                ->maxLength(2048),
                            Textarea::make('story_description')
                                ->label('Описание')
                                ->maxLength(5000)
                                ->rows(6)
                                ->columnSpanFull(),
                            FileUpload::make('story_image')
                                ->label('Изображение')
                                ->image()
                                ->directory('settings/story')
                                ->maxSize(5120)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Section::make('Баннеры')
                        ->schema([
                            FileUpload::make('banner_wide_image')
                                ->label('Широкий баннер')
                                ->image()
                                ->directory('settings/banners')
                                ->maxSize(5120)
                                ->columnSpanFull(),
                            FileUpload::make('banner_double_first_image')
                                ->label('Двойной баннер — первое изображение')
                                ->image()
                                ->directory('settings/banners')
                                ->maxSize(5120),
                            FileUpload::make('banner_double_second_image')
                                ->label('Двойной баннер — второе изображение')
                                ->image()
                                ->directory('settings/banners')
                                ->maxSize(5120),
                            FileUpload::make('banner_triple_first_image')
                                ->label('Тройной баннер — первое изображение')
                                ->image()
                                ->directory('settings/banners')
                                ->maxSize(5120),
                            FileUpload::make('banner_triple_second_image')
                                ->label('Тройной баннер — второе изображение')
                                ->image()
                                ->directory('settings/banners')
                                ->maxSize(5120),
                            FileUpload::make('banner_triple_third_image')
                                ->label('Тройной баннер — третье изображение')
                                ->image()
                                ->directory('settings/banners')
                                ->maxSize(5120),
                        ])
                        ->columns(2),
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
        $setting = $this->getRecord() ?? new Setting;
        $setting->fill($this->form->getState());
        $setting->save();

        if ($setting->wasRecentlyCreated) {
            $this->form->record($setting)->saveRelationships();
        }

        Notification::make()
            ->success()
            ->title('Настройки сохранены')
            ->send();
    }

    private function getRecord(): ?Setting
    {
        return Setting::query()->first();
    }
}
