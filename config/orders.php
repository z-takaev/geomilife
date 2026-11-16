<?php

declare(strict_types=1);

return [
    'reconciliation_stale_minutes' => 2,
    'saved_locations_limit' => 10,
    'cdek' => [
        'tariff_code' => 136,
        'shipment_point' => env('CDEK_SHIPMENT_POINT', 'VLZ2'),
        'order_number_prefix' => env('CDEK_ORDER_NUMBER_PREFIX', env('APP_ENV', 'production') === 'local' ? 'geomilife' : null),
        'city_code' => 1082,
        'sender_company' => env('CDEK_SENDER_COMPANY', 'GeoMiLife'),
        'sender_name' => env('CDEK_SENDER_NAME', 'Туаев Георгий Аслабекович'),
    ],
];
