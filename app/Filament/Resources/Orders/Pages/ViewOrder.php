<?php

declare(strict_types=1);

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\Cdek\CdekPrintStateEnum;
use App\Enums\Cdek\CdekRequestStateEnum;
use App\Enums\Cdek\CdekShipmentOperationStateEnum;
use App\Enums\Orders\OrderStatusEnum;
use App\Enums\Orders\PaymentStatusEnum;
use App\Filament\Resources\Orders\OrderResource;
use App\Jobs\Orders\CreateCdekShipmentJob;
use App\Jobs\Orders\PrepareCdekDocumentsJob;
use App\Services\Orders\CdekShipmentService;
use App\Services\Orders\OrderLifecycleService;
use App\Services\Orders\OrderPaymentService;
use App\ViewModel\Orders\OrderViewModel;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function processingActions(): array
    {
        $actions = [];

        foreach ([OrderStatusEnum::CONFIRMED, OrderStatusEnum::READY_FOR_PICKUP, OrderStatusEnum::READY_TO_SHIP, OrderStatusEnum::DELIVERED] as $status) {
            $actions[] = Action::make('status_'.$status->value)
                ->label(fn (): string => OrderViewModel::transitionLabel($this->getRecord(), $status))
                ->modalHeading(fn (): string => OrderViewModel::transitionLabel($this->getRecord(), $status))
                ->modalDescription(fn (): string => OrderViewModel::transitionDescription($this->getRecord(), $status))
                ->modalSubmitActionLabel('Подтвердить')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->nextStatus() === $status)
                ->action(fn () => $this->perform(fn () => app(OrderLifecycleService::class)->advanceStatus($this->getRecord(), $status, auth('filament')->user())));
        }

        return [
            ...$actions,

        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Отменить заказ')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription(fn (): string => OrderViewModel::cancellationDescription($this->getRecord()))
                ->schema([Textarea::make('reason')
                    ->label('Причина отмены')->required()->maxLength(1000)])
                ->visible(fn (): bool => ! in_array($this->getRecord()->status, [OrderStatusEnum::CANCELLED, OrderStatusEnum::DELIVERED, OrderStatusEnum::SHIPPED], true)
                    && $this->getRecord()->cancellation_state === null)
                ->action(fn (array $data) => $this->perform(fn () => app(OrderLifecycleService::class)->cancel($this->getRecord(), auth('filament')->user(), $data['reason']))),

            Action::make('retryCancel')
                ->label('Проверить / повторить отмену')
                ->requiresConfirmation()
                ->visible(fn (): bool => in_array($this->getRecord()->cancellation_state, ['PENDING', 'FAILED'], true))
                ->action(fn () => $this->perform(fn () => app(OrderLifecycleService::class)->resumeCancellation($this->getRecord()))),

            ActionGroup::make([
                Action::make('checkPayment')
                    ->label('Сверить оплату с банком')
                    ->authorize(fn (): bool => OrderResource::canView($this->getRecord()))
                    ->visible(fn (): bool => $this->getRecord()->payment_method->isOnline())
                    ->requiresConfirmation()
                    ->modalDescription('Будет запрошено состояние платежа в Т-Банке. Если заказ отменён и обнаружена оплата, будет запущен возврат.')
                    ->modalSubmitActionLabel('Сверить оплату')
                    ->action(fn () => $this->perform(function (): void {
                        foreach ($this->getRecord()->payments as $payment) {
                            app(OrderPaymentService::class)->syncPayment($payment);
                        }
                    })),

                Action::make('retryRefund')
                    ->label('Проверить / повторить возврат')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->getRecord()->status->isCancelled()
                        && in_array($this->getRecord()->payment_status, [PaymentStatusEnum::REFUND_PENDING, PaymentStatusEnum::REFUND_FAILED, PaymentStatusEnum::PAID], true)
                        && $this->getRecord()->payment_method->isOnline())
                    ->action(fn () => $this->perform(fn () => app(OrderPaymentService::class)->cancelOrRefundPayments($this->getRecord()))),

                Action::make('checkShipment')
                    ->label('Проверить СДЭК')
                    ->visible(fn (): bool => $this->getRecord()->shipment !== null)
                    ->action(fn () => $this->perform(fn () => app(CdekShipmentService::class)->sync($this->getRecord()))),

                Action::make('retryShipment')
                    ->label('Повторить регистрацию СДЭК')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->getRecord()->status->isReadyToShip()
                        && $this->getRecord()->cancellation_state === null
                        && $this->getRecord()->shipment?->uuid === null
                        && ! in_array($this->getRecord()->shipment?->operation_state, [
                            CdekShipmentOperationStateEnum::CREATING,
                            CdekShipmentOperationStateEnum::REGISTERING,
                        ], true))
                    ->action(fn () => $this->perform(fn () => CreateCdekShipmentJob::dispatch($this->getRecord()->id))),

                Action::make('prepareDocuments')
                    ->label('Проверить / повторить подготовку документов')
                    ->visible(fn (): bool => $this->getRecord()->shipment?->request_state?->is(CdekRequestStateEnum::SUCCESSFUL)
                        && ($this->getRecord()->documents()->count() < 2
                            || $this->getRecord()->documents()->whereIn('state', [
                                CdekPrintStateEnum::CREATING->value,
                                CdekPrintStateEnum::ACCEPTED->value,
                                CdekPrintStateEnum::PROCESSING->value,
                                CdekPrintStateEnum::INVALID->value,
                                CdekPrintStateEnum::REMOVED->value,
                            ])->exists()))
                    ->action(fn () => $this->perform(fn () => PrepareCdekDocumentsJob::dispatch($this->getRecord()->id))),

            ])->label('Дополнительно')->button(),

            Action::make('packingSlip')
                ->label('Накладная для сборки')
                ->url(fn (): string => route('admin.orders.packing-slip', $this->getRecord())),
        ];
    }

    private function nextStatus(): ?OrderStatusEnum
    {
        return app(OrderLifecycleService::class)->nextManualStatus($this->getRecord());
    }

    private function perform(callable $operation): void
    {
        try {
            $operation();
            Notification::make()->title('Операция выполнена / поставлена в обработку')->success()->send();
        } catch (ValidationException $exception) {
            Notification::make()->title('Операция недоступна')->body(implode(' ', $exception->validator->errors()->all()))->danger()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->title('Операция не завершена')->body('Проверьте состояние интеграции и повторите позже.')->danger()->send();
        }

        $this->getRecord()->refresh()->unsetRelations();
    }
}
