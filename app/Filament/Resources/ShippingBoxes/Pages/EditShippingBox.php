<?php

declare(strict_types=1);

namespace App\Filament\Resources\ShippingBoxes\Pages;

use App\Filament\Resources\ShippingBoxes\ShippingBoxResource;
use App\Models\ShippingBox;
use App\Services\Orders\OrderPackingService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditShippingBox extends EditRecord
{
    protected static string $resource = ShippingBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action): void {
                    $skus = $this->unpackableVariantSkus();

                    if ($skus === []) {
                        return;
                    }

                    $this->sendBlockingNotification('Упаковку нельзя удалить', $skus);
                    $action->halt();
                }),
        ];
    }

    protected function beforeSave(): void
    {
        $candidate = $this->getRecord()->replicate();
        $candidate->fill($this->data);

        if (! $this->packagingChanged($candidate)) {
            return;
        }

        $skus = $this->unpackableVariantSkus($candidate);

        if ($skus === []) {
            return;
        }

        $this->sendBlockingNotification('Упаковку нельзя изменить', $skus);
        $this->halt();
    }

    private function packagingChanged(ShippingBox $candidate): bool
    {
        foreach (['length', 'width', 'height', 'is_active'] as $field) {
            if ($candidate->{$field} !== $this->getRecord()->{$field}) {
                return true;
            }
        }

        return false;
    }

    private function unpackableVariantSkus(?ShippingBox $candidate = null): array
    {
        $boxes = ShippingBox::query()
            ->where('is_active', true)
            ->whereKeyNot($this->getRecord()->getKey())
            ->get();

        if ($candidate?->is_active) {
            $boxes->push($candidate);
        }

        return app(OrderPackingService::class)->unpackableActiveVariantSkus($boxes);
    }

    private function sendBlockingNotification(string $title, array $skus): void
    {
        $shownSkus = array_slice($skus, 0, 5);
        $suffix = count($skus) > 5 ? ' и другие' : '';

        Notification::make()
            ->danger()
            ->title($title)
            ->body(__('Активные варианты :skus не помещаются в оставшиеся упаковки.', [
                'skus' => implode(', ', $shownSkus).$suffix,
            ]))
            ->persistent()
            ->send();
    }
}
