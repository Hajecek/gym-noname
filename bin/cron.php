<?php

declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;
use App\Services\AppPushService;
use App\Services\MailService;
use App\Services\MembershipService;
use App\Services\ReservationService;

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
Env::load($root . '/.env');

if (PHP_SAPI !== 'cli') {
    $token = (string) Env::get('CRON_TOKEN', '');
    $provided = (string) ($_GET['token'] ?? '');
    if ($token === '' || !hash_equals($token, $provided)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

$db = new Database();
$expired = ReservationService::make($db)->expireHolds();
$memberships = new MembershipService($db);
$expiredMembers = $memberships->expireOverdue();
$push = AppPushService::make($db);
foreach ($expiredMembers as $userId) {
    if (!$memberships->activeForUser($userId)) {
        $push->membershipExpired($userId);
    }
}
$sent = (new MailService($db))->processPending(50);
$db->query('DELETE FROM rate_limit_events WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY)');
$db->query('DELETE FROM login_attempts WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 14 DAY)');
$retention = (int) (require $root . '/config/app.php')['gdpr_access_log_retention_days'] ?? 365;
$db->query('DELETE FROM access_logs WHERE created_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ' . $retention . ' DAY)');

$line = sprintf("[%s] cron holds=%d mail=%d\n", gmdate('c'), $expired, $sent);
file_put_contents($root . '/storage/logs/cron.log', $line, FILE_APPEND);
echo $line;
