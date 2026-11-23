<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Webhooks\CdekWebhookRequest;
use App\Jobs\Orders\SyncCdekShipmentJob;
use App\Models\OrderShipment;
use Illuminate\Http\Response;

final class CdekWebhookController extends Controller
{
    public function __invoke(CdekWebhookRequest $request): Response
    {
        $shipment = OrderShipment::query()
            ->where('uuid', $request->validated('uuid'))
            ->first();

        if ($shipment !== null) {
            SyncCdekShipmentJob::dispatch($shipment->order_id)->afterCommit();
        }

        return response()->noContent(200);
    }
}
