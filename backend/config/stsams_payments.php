<?php

declare(strict_types=1);

return [
    'secret_key' => env('STSAMS_PAYSTACK_SECRET_KEY'),
    'public_key' => env('STSAMS_PAYSTACK_PUBLIC_KEY'),
    'currency' => 'NGN',
    'base_url' => 'https://api.paystack.co',
];
