<?php

return [
    // Master switch to control visibility of all rating-related UI
    'enabled' => env('RATING_FEATURE_ENABLED', true),

    // Control which contexts are enabled (session, trade)
    'contexts' => [
        'session' => env('RATING_SESSION_ENABLED', true),
        'trade' => env('RATING_TRADE_ENABLED', true),
    ],
];


