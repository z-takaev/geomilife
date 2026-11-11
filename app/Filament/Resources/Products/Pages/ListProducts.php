<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Pages;

use App\Actions\Products\ExportProductsAction;
use App\Actions\Products\ImportProductsAction;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Экспорт в Excel')
                ->authorize(fn (): bool => auth('filament')->user()?->can('export', Product::class) ?? false)
                ->action(fn (): StreamedResponse => app(ExportProductsAction::class)->handle($this->getFilteredSortedTableQuery())),
            Action::make('importExcel')
                ->label('Импорт из Excel')
                ->authorize(fn (): bool => auth('filament')->user()?->can('create', Product::class) ?? false)
                ->modalHeading('Обновить товары из Excel')
                ->modalSubmitActionLabel('Применить изменения')
                ->steps([
                    Step::make('Файл')
                        ->schema([
                            FileUpload::make('file')
                                ->label('Файл Excel')
                                ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                ->maxSize(10240)
                                ->storeFiles(false)
                                ->showAllValidationMessages()
                                ->required(),
                        ])
                        ->afterValidation(fn (Get $get, Set $set) => $set('preview', $this->importFile($get('file'), preview: true))),
                    Step::make('Подтверждение')
                        ->schema([
                            Textarea::make('preview')->label('Изменения')->rows(14)->readOnly()->dehydrated(false),
                        ]),
                ])
                ->action(function (array $data): void {
                    $count = $this->importFile($data['file']);
                    $data['file']->delete();
                    Notification::make()->success()->title('Импорт завершён')->body('Обновлено записей: '.$count.'.')->send();
                    $this->resetTable();
                }),
            CreateAction::make(),
        ];
    }

    private function importFile(TemporaryUploadedFile $file, bool $preview = false): int|string
    {
        try {
            $import = app(ImportProductsAction::class);

            return $preview ? $import->preview($file->getRealPath()) : $import->handle($file->getRealPath());
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('Файл не импортирован')->body($exception->validator->errors()->first())->send();
            throw ValidationException::withMessages(['mountedActions.0.data.file' => $exception->validator->errors()->all()]);
        }
    }
}
