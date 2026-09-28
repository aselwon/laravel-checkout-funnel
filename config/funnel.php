<?php

return [
    'paid_enabled' => (bool) env('PAID_PATH_ENABLED', true),
    'mock_stripe' => (bool) env('MOCK_STRIPE', true),
    'stripe_secret' => env('STRIPE_SECRET'),
    'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'stripe_price_id' => env('STRIPE_PRICE_ID'),
    'price_cents' => 2900,
    'currency' => 'usd',
    'admin_email' => env('ADMIN_EMAIL', 'admin@sellerboost.test'),
    'admin_password' => env('ADMIN_PASSWORD'),
];
