<?php

declare(strict_types=1);

use App\Core\Application;

require __DIR__ . '/vendor/autoload.php';

if (!defined('PRIVOFIT_FRONT')) {
    define('PRIVOFIT_FRONT', true);
}

$app = Application::create();
$app->run();
