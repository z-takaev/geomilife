<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Webhooks\TbankWebhookRequest;
use App\Services\Orders\OrderPaymentService;
use App\Services\Tbank\TbankService;
use Illuminate\Http\Response;

final class TbankWebhookController extends Controller
{
    public function __invoke(TbankWebhookRequest $request, TbankService $tbank, OrderPaymentService $payments): Response
    {
        $payments->handleBankNotification($tbank->parsePaymentNotification($request->payload()));

        return response('OK', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
