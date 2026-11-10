<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Orders\OrderPaymentService;
use App\Services\Tbank\TbankService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use JsonException;

final class TbankWebhookController extends Controller
{
    public function __invoke(Request $request, TbankService $tbank, OrderPaymentService $payments): Response
    {
        abort_unless($request->isJson(), 415);

        try {
            $data = json_decode($request->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['notification' => 'Некорректный JSON уведомления Т-Банка.']);
        }

        if (! is_array($data) || array_is_list($data)) {
            throw ValidationException::withMessages(['notification' => 'Ожидается JSON-объект уведомления Т-Банка.']);
        }

        $payments->handleBankNotification($tbank->parsePaymentNotification($data));

        return response('OK', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
