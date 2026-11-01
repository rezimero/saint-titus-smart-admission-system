<?php

declare(strict_types=1);

return [
    // Default closed in production; keep local development functional.
    'admissions_enabled' => (bool) env('STSAMS_PUBLIC_ADMISSIONS_ENABLED', env('APP_ENV', 'local') !== 'production'),
    'payments_enabled' => (bool) env('STSAMS_PAYMENTS_ENABLED', false),
];
