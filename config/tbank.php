<?php

declare(strict_types=1);

return [
    'base_url' => env('TBANK_BASE_URL'),
    'terminal_key' => env('TBANK_TERMINAL_KEY'),
    'password' => env('TBANK_PASSWORD'),
    'connect_timeout' => (int) env('TBANK_CONNECT_TIMEOUT', 5),
    'timeout' => (int) env('TBANK_TIMEOUT', 15),
];
