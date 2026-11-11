<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Filament\Pages\ManageSettings;
use App\Models\FilamentUser;
use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
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
        ->assertSchemaStateSet(['benefits' => SettingSeeder::DEFAULT_BENEFITS]);

    Auth::shouldUse('web');
    $response = $this->get(route('home'))->assertOk();
    foreach (SettingSeeder::DEFAULT_BENEFITS as $benefit) {
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

it('сохраняет тексты пяти преимуществ и выводит изменения на главной с обновлением кеша', function (): void {
    $benefits = SettingSeeder::DEFAULT_BENEFITS;
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

    expect(Setting::query()->sole()->benefits)->toBe(SettingSeeder::DEFAULT_BENEFITS);
})->with([0, 4, 6]);

it('не сохраняет пустой текст преимущества', function (string $field): void {
    $benefits = SettingSeeder::DEFAULT_BENEFITS;
    $benefits[0][$field] = '';

    $component = Livewire::test(ManageSettings::class)
        ->fillForm([...$this->formData, 'benefits' => $benefits]);
    $itemKey = array_key_first($component->get('data.benefits'));
    $component->call('save')->assertHasFormErrors(["benefits.{$itemKey}.{$field}" => 'required']);

    expect(Setting::query()->sole()->benefits)->toBe(SettingSeeder::DEFAULT_BENEFITS);
})->with(['title', 'description']);
