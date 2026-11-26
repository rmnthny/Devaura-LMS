<?php

return [
    'timezone' => 'Asia/Manila',
    'kiosk_url' => env('KIOSK_URL', 'http://127.0.0.1:8001'),
    'opens_at' => env('LIBRARY_OPENS_AT', '08:00'),
    'afternoon_at' => env('LIBRARY_AFTERNOON_AT', '12:00'),
    'closes_at' => env('LIBRARY_CLOSES_AT', '17:00'),
];
