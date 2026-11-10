<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderDocument;
use App\Services\Cdek\CdekService;
use App\Services\Cdek\Exceptions\CdekApiException;
use App\ViewModel\Orders\PackingSlipViewModel;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OrderDocumentService
{
    /**
     * Подключает сервис получения документов СДЭК.
     */
    public function __construct(private readonly CdekService $cdek) {}

    /**
     * Формирует упаковочный лист заказа и возвращает содержимое PDF.
     */
    public function renderPackingSlip(Order $order): string
    {
        $order->loadMissing('items', 'shipment');
        $options = new Options([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'tempDir' => storage_path('framework/cache'),
            'fontCache' => storage_path('framework/cache'),
        ]);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('orders.packing-slip', (new PackingSlipViewModel($order))->data())->render());
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }

    /**
     * Запускает подготовку квитанции и наклеек СДЭК и обновляет их готовность.
     */
    public function prepareCdekDocuments(Order $order): void
    {
        foreach (['WAYBILL', 'BARCODES'] as $type) {
            $document = $this->prepareDocument($order, $type);

            if ($document->uuid !== null && in_array($document->state, ['ACCEPTED', 'PROCESSING'], true)) {
                $result = $type === 'WAYBILL' ? $this->cdek->getWaybill($document->uuid) : $this->cdek->getBarcodes($document->uuid);

                $document->update([
                    'state' => $result->status?->value ?? $document->state,
                    'error' => $result->operation->errors[0]['message'] ?? null,
                ]);
            }
        }
    }

    /**
     * Проверяет готовность документа СДЭК и возвращает содержимое PDF.
     */
    public function downloadCdekDocument(Order $order, string $type): string
    {
        $document = $order->documents()->where('type', $type)->first();

        if ($document === null || $document->uuid === null || in_array($document->state, ['INVALID', 'REMOVED'], true)) {
            $document = $this->prepareDocument($order, $type);
        }

        if ($document->uuid === null) {
            throw ValidationException::withMessages(['document' => __('Документ готовится. Повторите скачивание позже.')]);
        }

        if ($document->state !== 'READY') {
            $result = $type === 'WAYBILL' ? $this->cdek->getWaybill($document->uuid) : $this->cdek->getBarcodes($document->uuid);

            $document->update(['state' => $result->status?->value, 'error' => $result->operation->errors[0]['message'] ?? null]);

            if (! $result->status?->isReady()) {
                throw ValidationException::withMessages(['document' => __('Документ ещё не готов. Повторите скачивание позже.')]);
            }
        }

        try {
            return $type === 'WAYBILL' ? $this->cdek->downloadWaybill($document->uuid) : $this->cdek->downloadBarcodes($document->uuid);
        } catch (CdekApiException $exception) {
            if ($exception->statusCode !== 404) {
                throw $exception;
            }

            $document->update(['state' => 'REMOVED', 'error' => $exception->getMessage()]);
            $this->prepareDocument($order, $type);
            throw ValidationException::withMessages(['document' => __('Документ формируется повторно. Повторите скачивание позже.')]);
        }
    }

    private function prepareDocument(Order $order, string $type): OrderDocument
    {
        if (! in_array($type, ['WAYBILL', 'BARCODES'], true)) {
            throw ValidationException::withMessages(['document' => __('Неизвестный тип документа.')]);
        }

        $shipment = $order->shipment()->first();

        if ($shipment?->uuid === null || $shipment->request_state !== 'SUCCESSFUL') {
            throw ValidationException::withMessages(['document' => __('Дождитесь успешной регистрации отправления СДЭК.')]);
        }

        $document = $order->documents()->firstOrCreate(['type' => $type]);
        $creationClaimed = $this->claimDocumentCreation($document);

        if (! $creationClaimed) {
            return $document->fresh();
        }

        $this->requestDocument($order, $document, $shipment->uuid, $type);

        return $document->fresh();
    }

    private function claimDocumentCreation(OrderDocument $document): bool
    {
        return DB::transaction(function () use ($document): bool {
            $lockedDocument = OrderDocument::query()
                ->lockForUpdate()
                ->findOrFail($document->id);

            if (($lockedDocument->uuid !== null && ! in_array($lockedDocument->state, ['INVALID', 'REMOVED'], true))
                || ($lockedDocument->state === 'CREATING' && $lockedDocument->updated_at->gt(now()->subMinutes((int) config('orders.reconciliation_stale_minutes'))))) {
                return false;
            }

            $lockedDocument->update(['uuid' => null, 'state' => 'CREATING', 'error' => null]);

            return true;
        });
    }

    private function requestDocument(Order $order, OrderDocument $document, string $shipmentUuid, string $type): void
    {
        try {
            $result = $type === 'WAYBILL' ? $this->cdek->createWaybill($shipmentUuid) : $this->cdek->createBarcodes($shipmentUuid);

            $document->update([
                'uuid' => $result->uuid,
                'state' => $result->errors === [] ? 'ACCEPTED' : 'INVALID',
                'error' => $result->errors[0]['message'] ?? null,
            ]);
        } catch (Throwable $exception) {
            $document->update([
                'state' => $exception instanceof CdekApiException && ($exception->statusCode === null || $exception->statusCode >= 500) ? 'CREATING' : 'INVALID',
                'error' => $exception->getMessage(),
            ]);
            app(OrderNotificationService::class)->send($order, 'DOCUMENT_FAILED', 'document-failed:'.$type.':'.hash('sha256', $exception->getMessage()));
            throw $exception;
        }
    }
}
