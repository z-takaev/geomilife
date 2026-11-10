<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\Orders\SyncCdekShipmentJob;
use App\Models\OrderShipment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CdekWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->isJson(), 415);

        $request->validate([
            'type' => ['required', 'string', 'in:ORDER_STATUS'],
            'date_time' => ['required', 'date'],
            'uuid' => ['required', 'uuid'],
            'attributes' => ['required', 'array'],
            'attributes.is_return' => ['required', 'boolean'],
            'attributes.cdek_number' => ['required', 'string'],
            'attributes.status_code' => ['required', 'string'],
            'attributes.status_date_time' => ['required', 'date'],
            'attributes.code' => ['required', 'string'],
            'attributes.is_reverse' => ['required', 'boolean'],
            'attributes.is_client_return' => ['required', 'boolean'],
        ]);

        $shipment = OrderShipment::query()
            ->where('uuid', $request->input('uuid'))
            ->first();

        if ($shipment !== null) {
            SyncCdekShipmentJob::dispatch($shipment->order_id)->afterCommit();
        }

        return response()->noContent(200);
    }
}
