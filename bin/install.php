<?php

declare(strict_types=1);

/**
 * PRIVOFIT installer
 *
 * php bin/install.php --generate-key
 * php bin/install.php --email=owner@example.com --username=majitel --password='dlouhe-heslo-min-12' --first=Jan --last=Novak
 */

use App\Core\Crypto;
use App\Core\Database;
use App\Core\Env;
use App\Database\Migrator;
use App\Database\Seeder;
use App\Support\Clock;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        [$k, $v] = array_pad(explode('=', substr($arg, 2), 2), 2, '1');
        $opts[$k] = $v;
    }
}

if (isset($opts['generate-key'])) {
    echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;
    exit(0);
}

$envFile = $root . '/.env';
if (!is_file($envFile)) {
    copy($root . '/.env.example', $envFile);
    $key = 'base64:' . base64_encode(random_bytes(32));
    $contents = (string) file_get_contents($envFile);
    $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $contents) ?? $contents;
    file_put_contents($envFile, $contents);
    echo "Vytvořen soubor .env s novým APP_KEY.\nUpravte databázové údaje a spusťte instalaci znovu.\n";
    exit(0);
}

Env::load($envFile);
$lock = $root . '/storage/app/installed.lock';
if (is_file($lock) && !isset($opts['force'])) {
    fwrite(STDERR, "Instalace je už dokončena. Pro opětovné spuštění použijte --force.\n");
    exit(1);
}

$email = strtolower(trim((string) ($opts['email'] ?? '')));
$username = strtolower(trim((string) ($opts['username'] ?? '')));
$password = (string) ($opts['password'] ?? '');
$first = (string) ($opts['first'] ?? 'Owner');
$last = (string) ($opts['last'] ?? 'PRIVOFIT');

if ($email === '' || $username === '' || strlen($password) < 12) {
    fwrite(STDERR, "Použití:\nphp bin/install.php --email=owner@domena.cz --username=majitel --password='min-12-znaku' --first=Jmeno --last=Prijmeni\n");
    exit(1);
}

$dbName = (string) Env::get('DB_DATABASE', 'privofit');
$host = (string) Env::get('DB_HOST', '127.0.0.1');
$port = (string) Env::get('DB_PORT', '3306');
$user = (string) Env::get('DB_USERNAME', 'root');
$pass = (string) Env::get('DB_PASSWORD', '');

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port),
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

$db = new Database();
$ran = (new Migrator($db, $root . '/database/migrations'))->run();
echo 'Migrace: ' . ($ran ? implode(', ', $ran) : 'žádné nové') . PHP_EOL;
(new Seeder($db))->run();
echo "Seed data vložena.\n";

$existing = $db->fetch('SELECT id FROM users WHERE email = :e OR username = :u', ['e' => $email, 'u' => $username]);
if ($existing) {
    fwrite(STDERR, "Uživatel s tímto e-mailem nebo username už existuje.\n");
    exit(1);
}

$now = Clock::utc();
$adminId = (int) $db->insert('users', [
    'public_id' => Crypto::uuid(),
    'email' => $email,
    'username' => $username,
    'password_hash' => Crypto::hashPassword($password),
    'first_name' => $first,
    'last_name' => $last,
    'role' => 'admin',
    'plan' => 'free',
    'status' => 'active',
    'email_verified_at' => $now,
    'terms_accepted_at' => $now,
    'privacy_accepted_at' => $now,
    'created_at' => $now,
    'updated_at' => $now,
]);
$role = $db->fetch("SELECT id FROM roles WHERE slug = 'admin'");
if ($role) {
    $db->insert('user_roles', [
        'user_id' => $adminId,
        'role_id' => (int) $role['id'],
        'assigned_at' => $now,
    ]);
}
$db->insert('notification_preferences', ['user_id' => $adminId]);

if (!is_dir($root . '/storage/app')) {
    mkdir($root . '/storage/app', 0750, true);
}
file_put_contents($lock, $now);
$env = (string) file_get_contents($envFile);
$env = preg_replace('/^INSTALL_ENABLED=.*$/m', 'INSTALL_ENABLED=false', $env) ?? $env;
file_put_contents($envFile, $env);

$iconDir = $root . '/assets/icons';
if (!is_dir($iconDir)) {
    mkdir($iconDir, 0755, true);
}
foreach ([192, 512] as $size) {
    $im = imagecreatetruecolor($size, $size);
    $bg = imagecolorallocate($im, 11, 18, 32);
    $accent = imagecolorallocate($im, 66, 232, 180);
    imagefilledrectangle($im, 0, 0, $size, $size, $bg);
    imagefilledellipse($im, (int) ($size / 2), (int) ($size / 2), (int) ($size * 0.42), (int) ($size * 0.42), $accent);
    imagepng($im, $iconDir . '/icon-' . $size . '.png');
    imagedestroy($im);
}

echo "OWNER účet vytvořen: {$email} / @{$username}\n";
echo "Instalace dokončena. Soubor storage/app/installed.lock deaktivuje opakovanou instalaci.\n";
echo "Nastavte MFA ihned po prvním přihlášení.\n";
