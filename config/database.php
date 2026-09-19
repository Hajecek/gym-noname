<?php

declare(strict_types=1);

return [
    'connection' => env_value('DB_CONNECTION', 'mysql'),
    'host' => env_value('DB_HOST', '127.0.0.1'),
    'port' => env_value('DB_PORT', '3306'),
    'database' => env_value('DB_DATABASE', 'privofit'),
    'username' => env_value('DB_USERNAME', 'root'),
    'password' => env_value('DB_PASSWORD', ''),
    'charset' => env_value('DB_CHARSET', 'utf8mb4'),
    'collation' => env_value('DB_COLLATION', 'utf8mb4_unicode_ci'),
];
