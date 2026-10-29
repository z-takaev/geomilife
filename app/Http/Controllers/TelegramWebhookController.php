<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;

final class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = (string) config('telegram.webhook_secret');
        abort_if($secret === '' || blank(config('telegram.bot_token')), 503);
        abort_unless(hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')), 403);

        $request->validate(['update_id' => ['required', 'integer']]);

        $bot = app(Nutgram::class);
        $bot->setRunningMode(Webhook::class);
        $bot->run();

        return response()->noContent();
    }
}
