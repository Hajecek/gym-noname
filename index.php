<?php

declare(strict_types=1);

use App\Core\Application;

require __DIR__ . '/vendor/autoload.php';

$app = Application::create();
$app->run();
