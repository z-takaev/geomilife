<?php

declare(strict_types=1);

return [
    'payment_timeout_minutes' => 60,
    'reconciliation_stale_minutes' => 2,
    'saved_locations_limit' => 10,
    'cdek' => [
        'tariff_code' => 136,
        'shipment_point' => env('CDEK_SHIPMENT_POINT', 'VLZ2'),
        'city_code' => 1082,
        'sender_company' => env('CDEK_SENDER_COMPANY', 'GeoMiLife'),
        'sender_name' => env('CDEK_SENDER_NAME', 'Туаев Георгий Аслабекович'),
    ],
];
