<?php

declare(strict_types=1);

return [
    'mail' => [
        'mailer' => env_value('MAIL_MAILER', 'log'),
        'host' => env_value('MAIL_HOST', 'smtp.example.com'),
        'port' => (int) env_value('MAIL_PORT', 587),
        'username' => env_value('MAIL_USERNAME', ''),
        'password' => env_value('MAIL_PASSWORD', ''),
        'encryption' => env_value('MAIL_ENCRYPTION', 'tls'),
        'from_address' => env_value('MAIL_FROM_ADDRESS', 'noreply@privofit.cz'),
        'from_name' => env_value('MAIL_FROM_NAME', 'PRIVOFIT'),
    ],
    'door' => [
        'provider' => env_value('DOOR_PROVIDER', 'mock'),
        'nuki_base' => env_value('NUKI_API_BASE', 'https://api.nuki.io'),
        'nuki_token' => env_value('NUKI_API_TOKEN', ''),
        'nuki_smartlock_id' => env_value('NUKI_SMARTLOCK_ID', ''),
        'nuki_action' => env_value('NUKI_ACTION', 'unlatch'),
        'nuki_timeout' => (int) env_value('NUKI_TIMEOUT', 15),
    ],
    'payment' => [
        'provider' => env_value('PAYMENT_PROVIDER', 'manual'),
        'stripe_secret' => env_value('STRIPE_SECRET_KEY', ''),
        'stripe_publishable' => env_value('STRIPE_PUBLISHABLE_KEY', ''),
    ],
    'cron_token' => env_value('CRON_TOKEN', ''),
];
