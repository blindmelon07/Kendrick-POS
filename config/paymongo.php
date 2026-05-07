<?php

return [

    'secret_key'     => env('PAYMONGO_SECRET_KEY', ''),
    'public_key'     => env('PAYMONGO_PUBLIC_KEY', ''),
    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET', ''),

    'base_url' => 'https://api.paymongo.com/v1',

    'payment_methods' => ['gcash', 'paymaya', 'card', 'grab_pay', 'qrph'],

];
