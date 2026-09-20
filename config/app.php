<?php

declare(strict_types=1);

return [
    'name' => env_value('APP_NAME', 'PRIVOFIT'),
    'env' => env_value('APP_ENV', 'local'),
    'debug' => (bool) env_value('APP_DEBUG', false),
    'url' => rtrim((string) env_value('APP_URL', '/privofit'), '/'),
    'timezone' => env_value('APP_TIMEZONE', 'UTC'),
    'display_timezone' => env_value('APP_DISPLAY_TIMEZONE', 'Europe/Prague'),
    'locale' => env_value('APP_LOCALE', 'cs-CZ'),
    'currency' => env_value('APP_CURRENCY', 'CZK'),
    'reserved_usernames' => [
        'admin', 'administrator', 'support', 'privofit', 'system', 'owner', 'staff',
        'root', 'api', 'www', 'mail', 'help', 'security', 'null', 'undefined',
        'test', 'superadmin', 'super_admin', 'moderator', 'info', 'contact',
    ],
    'username_change_days' => 14,
    'password_min_length' => 12,
    'email_verify_ttl_minutes' => 60,
    'password_reset_ttl_minutes' => 30,
    'access_token_ttl_minutes' => 15,
    'refresh_token_ttl_days' => 30,
    'reservation_hold_minutes' => 15,
    'cancellation_hours' => 12,
    'early_access_minutes' => 10,
    'late_access_minutes' => 10,
    'gdpr_access_log_retention_days' => 365,
    'gdpr_audit_log_retention_days' => 730,
];
