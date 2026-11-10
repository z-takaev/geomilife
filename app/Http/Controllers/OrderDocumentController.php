<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\OrderDocumentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OrderDocumentController extends Controller
{
    public function packingSlip(Order $order, OrderDocumentService $documents): StreamedResponse
    {
        return $this->download($documents->renderPackingSlip($order), 'order-'.$order->id.'-packing-slip.pdf');
    }

    public function waybill(Order $order, OrderDocumentService $documents): StreamedResponse
    {
        return $this->download($documents->downloadCdekDocument($order, 'WAYBILL'), 'order-'.$order->id.'-cdek-waybill.pdf');
    }

    public function barcodes(Order $order, OrderDocumentService $documents): StreamedResponse
    {
        return $this->download($documents->downloadCdekDocument($order, 'BARCODES'), 'order-'.$order->id.'-cdek-barcodes.pdf');
    }

    private function download(string $contents, string $filename): StreamedResponse
    {
        return response()->streamDownload(static function () use ($contents): void {
            echo $contents;
        }, $filename, ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store']);
    }
}
