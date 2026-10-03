<?php
declare(strict_types=1);

return [
    'api_url' => env('API_URL', 'https://api.costs-to-expect.com'),
    'api_url_dev' => env('API_URL_DEV', 'https://api.costs-to-expect.com'),
    'dev' => env('APP_DEV', false),
    'cache' => env('APP_CACHE', false),
    'item_type_id' => env('ITEM_TYPE_ID'),
    'item_subtype_id' => env('ITEM_SUBTYPE_ID'),
    'error_email' => env('ERROR_EMAIL'),
    'internal_key' => env('COSTS_TO_EXPECT_INTERNAL_API_KEY'),
    'cookie_user' => env('SESSION_NAME_USER'),
    'cookie_bearer' => env('SESSION_NAME_BEARER'),
    // Undo, change and clear a score. They remove a key from the stored score sheet, which needs the API to replace
    // the sheet it is sent, switch them on once that has been confirmed against the API
    'score_corrections' => (bool) env('SCORE_CORRECTIONS', false),
];
