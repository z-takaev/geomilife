<?php

declare(strict_types=1);

use App\Enums\Filament\FilamentUserRoleEnum;
use App\Filament\Pages\ManageSettings;
use App\Models\FilamentUser;
use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CheckoutFixtures;

beforeEach(function (): void {
    Storage::fake('public');
    CheckoutFixtures::privacyPolicy();
    $this->setting = Setting::query()->sole();
    $this->document = $this->setting->privacy_policy;
    $this->pdf = Storage::disk('public')->get($this->document);
    $admin = FilamentUser::query()->create([
        'name' => 'Администратор', 'email' => 'pdf@example.com', 'password' => 'password',
        'role' => FilamentUserRoleEnum::ADMIN,
    ]);
    $this->actingAs($admin, 'filament');
    Filament::setCurrentPanel('admin');
    $this->formData = [
        'phone' => '+7 (988) 375-75-55', 'email' => 'store@example.com',
        'admin_email' => 'admin@example.com', 'address' => 'Мира, 10', 'working_hours' => 'Пн–Пт',
    ];
});

it('использует обычное поле файла с открытием и скачиванием PDF', function (): void {
    Livewire::test(ManageSettings::class)
        ->assertFormFieldExists('privacy_policy', fn ($field): bool => $field::class === FileUpload::class
            && ! $field->isPreviewable() && $field->isOpenable() && $field->isDownloadable());
});

it('заменяет PDF вместе с настройками только после сохранения', function (): void {
    $oldUrl = Setting::cached()->privacyPolicyUrl();
    $component = Livewire::test(ManageSettings::class)
        ->fillForm([...$this->formData, 'privacy_policy' => [UploadedFile::fake()->createWithContent('new-policy.pdf', $this->pdf)]]);
    expect($this->setting->fresh()->privacy_policy)->toBe($this->document);
    Storage::disk('public')->assertExists($this->document);
    $component->call('save')->assertHasNoFormErrors();
    $setting = $this->setting->fresh();
    expect($setting->privacy_policy)->not->toBe($this->document)
        ->and($setting->email)->toBe('store@example.com')
        ->and(Storage::disk('public')->get($setting->privacy_policy))->toBe($this->pdf)
        ->and(Setting::cached()->privacyPolicyUrl())->not->toBe($oldUrl);
    Storage::disk('public')->assertMissing($this->document);
});

it('сохраняет текущий PDF при пустой или неподходящей замене', function (bool $withFile): void {
    $file = $withFile ? [UploadedFile::fake()->createWithContent('invalid.txt', 'Текст вместо PDF')] : [];
    Livewire::test(ManageSettings::class)->fillForm([...$this->formData, 'privacy_policy' => $file])
        ->call('save')->assertHasFormErrors(['privacy_policy']);
    expect($this->setting->fresh()->privacy_policy)->toBe($this->document);
    Storage::disk('public')->assertExists($this->document);
})->with([true, false]);

it('сохраняет PDF при ошибках остальных полей и при сохранении без замены', function (): void {
    Livewire::test(ManageSettings::class)
        ->fillForm([...$this->formData, 'email' => 'invalid', 'privacy_policy' => [UploadedFile::fake()->createWithContent('new.pdf', $this->pdf)]])
        ->call('save')->assertHasFormErrors(['email']);
    expect($this->setting->fresh()->privacy_policy)->toBe($this->document);
    Storage::disk('public')->assertExists($this->document);
    Livewire::test(ManageSettings::class)->fillForm($this->formData)->call('save')->assertHasNoFormErrors();
    expect($this->setting->fresh()->privacy_policy)->toBe($this->document);
    Storage::disk('public')->assertExists($this->document);
});

it('не позволяет подставить путь к другому файлу', function (): void {
    Storage::disk('public')->put('another.pdf', $this->pdf);
    Livewire::test(ManageSettings::class)->fillForm([...$this->formData, 'privacy_policy' => ['another.pdf']])
        ->call('save')->assertHasFormErrors(['privacy_policy']);
    expect($this->setting->fresh()->privacy_policy)->toBe($this->document);
    Storage::disk('public')->assertExists($this->document);
    Storage::disk('public')->assertExists('another.pdf');
});

it('отклоняет PDF больше 10 МБ и оставляет текущий документ', function (): void {
    Livewire::test(ManageSettings::class)
        ->fillForm([...$this->formData, 'privacy_policy' => [UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')]])
        ->call('save')->assertHasFormErrors(['privacy_policy']);
    expect($this->setting->fresh()->privacy_policy)->toBe($this->document);
    Storage::disk('public')->assertExists($this->document);
});
