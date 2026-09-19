<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

\App\Core\Env::load(dirname(__DIR__) . '/.env');
\App\Core\Env::set('APP_ENV', 'testing');
date_default_timezone_set('UTC');

try {
    \App\Core\Application::create();
} catch (\Throwable) {
    // jednotkové testy běží i bez databáze
}
