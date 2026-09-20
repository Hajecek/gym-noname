<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Database\Migrator;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

Env::load($root . '/.env');
$db = new Database();
$ran = (new Migrator($db, $root . '/database/migrations'))->run();
echo 'Migrace: ' . ($ran ? implode(', ', $ran) : 'žádné nové') . PHP_EOL;
