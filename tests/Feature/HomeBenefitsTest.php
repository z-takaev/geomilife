<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Filament\Pages\ManageSettings;
use App\Models\FilamentUser;
use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

beforeEach(function (): void {
    $this->undoRepeaterFake = Repeater::fake();
    Storage::fake('public');
    CheckoutFixtures::privacyPolicy();
    $admin = FilamentUser::query()->create([
        'name' => 'Администратор',
        'email' => 'benefits@example.com',
        'password' => 'password',
        'role' => FilamentUserRoleEnum::ADMIN,
    ]);
    $this->actingAs($admin, 'filament');
    Filament::setCurrentPanel('admin');
    $this->formData = [
        'phone' => '+7 (988) 375-75-55',
        'email' => 'store@example.com',
        'admin_email' => 'admin@example.com',
        'address' => 'Мира, 10',
        'working_hours' => 'Пн–Пт',
    ];
});

afterEach(function (): void {
    ($this->undoRepeaterFake)();
});

it('показывает пять исходных преимуществ на сайте и в форме без создания и удаления', function (): void {
    Livewire::test(ManageSettings::class)
        ->assertFormFieldExists('benefits', fn (Repeater $field): bool => ! $field->isAddable()
            && ! $field->isDeletable() && ! $field->isReorderable()
            && $field->isCollapsible() && $field->isCollapsed()
            && $field->hasItemLabels())
        ->assertSchemaStateSet(['benefits' => SettingSeeder::BENEFITS]);

    Auth::shouldUse('web');
    $response = $this->get(route('home'))->assertOk();
    foreach (SettingSeeder::BENEFITS as $benefit) {
        $response->assertSee($benefit['title'])->assertSee($benefit['description']);
    }
});

it('располагает настройки по пяти табам начиная с контактной информации', function (): void {
    $component = Livewire::test(ManageSettings::class);
    $tabs = collect($component->instance()->form->getFlatComponents(withHidden: true))
        ->first(static fn (object $component): bool => $component instanceof Tabs);

    expect($tabs)->toBeInstanceOf(Tabs::class)
        ->and($tabs->getActiveTab())->toBe(1)
        ->and($tabs->isTabPersisted())->toBeTrue();

    $tabFields = collect($tabs->getChildSchema()->getComponents(withHidden: true))
        ->mapWithKeys(static fn (Tab $tab): array => [
            (string) $tab->getLabel() => array_keys($tab->getChildSchema()->getFlatComponents(withHidden: true)),
        ]);

    expect($tabFields->keys()->all())->toBe([
        'Контактная информация',
        'Преимущества',
        'Наша история',
        'Баннеры',
        'Общее',
    ])->and($tabFields['Контактная информация'])->toContain(
        'phone',
        'email',
        'admin_email',
        'address',
        'working_hours',
        'yandex_map_id',
        'instagram_url',
        'youtube_url',
        'vk_url',
        'telegram_url',
        'whatsapp_url',
    )->and($tabFields['Общее'])->toContain(
        'snow_enabled',
        'footer_title',
        'footer_description',
        'privacy_policy',
    );
});

it('группирует изображения баннеров по раскладкам и позициям', function (): void {
    $component = Livewire::test(ManageSettings::class);
    $tabs = collect($component->instance()->form->getFlatComponents(withHidden: true))
        ->first(static fn (object $component): bool => $component instanceof Tabs);
    $bannersTab = collect($tabs->getChildSchema()->getComponents(withHidden: true))
        ->first(static fn (Tab $tab): bool => $tab->getLabel() === 'Баннеры');
    $sections = collect($bannersTab->getChildSchema()->getComponents(withHidden: true));

    expect($sections->map(static fn (Section $section): string => (string) $section->getHeading())->all())->toBe([
        'Широкий баннер',
        'Двойной баннер',
        'Тройной баннер',
    ]);

    $groupedFields = $sections->mapWithKeys(static fn (Section $section): array => [
        (string) $section->getHeading() => collect($section->getChildSchema()->getComponents(withHidden: true))
            ->filter(static fn (object $component): bool => $component instanceof Fieldset)
            ->mapWithKeys(static fn (Fieldset $fieldset): array => [
                (string) $fieldset->getLabel() => array_values(array_filter(
                    array_keys($fieldset->getChildSchema()->getFlatComponents(withHidden: true)),
                    'is_string',
                )),
            ])->all(),
    ]);

    expect(array_values(array_filter(
        array_keys($sections[0]->getChildSchema()->getFlatComponents(withHidden: true)),
        'is_string',
    )))->toBe([
        'banner_wide_image',
        'banner_wide_mobile_image',
    ])->and($groupedFields['Двойной баннер'])->toBe([
        'Первый баннер' => ['banner_double_first_image', 'banner_double_first_mobile_image'],
        'Второй баннер' => ['banner_double_second_image', 'banner_double_second_mobile_image'],
    ])->and($groupedFields['Тройной баннер'])->toBe([
        'Первый баннер' => ['banner_triple_first_image', 'banner_triple_first_mobile_image'],
        'Второй баннер' => ['banner_triple_second_image', 'banner_triple_second_mobile_image'],
        'Третий баннер' => ['banner_triple_third_image', 'banner_triple_third_mobile_image'],
    ]);
});

it('сохраняет тексты пяти преимуществ и выводит изменения на главной с обновлением кеша', function (): void {
    $benefits = SettingSeeder::BENEFITS;
    $benefits[0] = ['title' => 'Новое преимущество', 'description' => '<script>Новый текст</script>'];
    Setting::cached();

    Livewire::test(ManageSettings::class)
        ->fillForm([...$this->formData, 'benefits' => $benefits])
        ->call('save')->assertHasNoFormErrors();

    expect(Setting::query()->sole()->benefits)->toBe($benefits)
        ->and(Setting::cached()->benefits)->toBe($benefits);

    Livewire::test(ManageSettings::class)->assertSchemaStateSet(['benefits' => $benefits]);
    Auth::shouldUse('web');
    $this->get(route('home'))->assertOk()
        ->assertSee('Новое преимущество')->assertSee($benefits[0]['description'])
        ->assertDontSee($benefits[0]['description'], false)
        ->assertSee('icon-Plant', false)->assertSee('icon-Certificate', false);
});

it('отклоняет изменение количества преимуществ', function (int $count): void {
    $benefits = array_fill(0, $count, ['title' => 'Заголовок', 'description' => 'Описание']);

    Livewire::test(ManageSettings::class)
        ->fillForm([...$this->formData, 'benefits' => $benefits])
        ->call('save')->assertHasFormErrors(['benefits']);

    expect(Setting::query()->sole()->benefits)->toBe(SettingSeeder::BENEFITS);
})->with([0, 4, 6]);

it('не сохраняет пустой текст преимущества', function (string $field): void {
    $benefits = SettingSeeder::BENEFITS;
    $benefits[0][$field] = '';

    $component = Livewire::test(ManageSettings::class)
        ->fillForm([...$this->formData, 'benefits' => $benefits]);
    $itemKey = array_key_first($component->get('data.benefits'));
    $component->call('save')->assertHasFormErrors(["benefits.{$itemKey}.{$field}" => 'required']);

    expect(Setting::query()->sole()->benefits)->toBe(SettingSeeder::BENEFITS);
})->with(['title', 'description']);
