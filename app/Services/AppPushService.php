<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Support\Clock;

final class AppPushService
{
    public function __construct(private readonly Database $db)
    {
    }

    public static function make(Database $db): self
    {
        return new self($db);
    }

    public function accountStatusChanged(array $user, string $status): void
    {
        $title = match ($status) {
            'blocked' => 'Účet byl zablokován',
            'active' => 'Účet je znovu aktivní',
            default => 'Stav účtu se změnil',
        };
        $body = match ($status) {
            'blocked' => 'Přístup do PRIVOFIT je teď pozastavený. Ozvi se nám, pokud to chceš řešit.',
            'active' => 'Účet je zase v pořádku. Můžeš rezervovat a otevírat dveře.',
            default => 'Otevři aplikaci, stav tvého účtu se změnil.',
        };
        $this->notify((int) $user['id'], 'account-status', $title, $body, 'account.sync');
    }

    public function membershipAssigned(int $userId): void
    {
        $this->notify(
            $userId,
            'membership-assigned',
            'Členství je aktivní',
            'V aplikaci uvidíš nový tarif a zbývající vstupy.',
            'membership.sync'
        );
    }

    public function membershipExpired(int $userId): void
    {
        $this->notify(
            $userId,
            'membership-expired',
            'Členství skončilo',
            'Platnost tarifu vypršela. Nové rezervace ze členství teď nejdou.',
            'membership.sync'
        );
    }

    public function notify(int $userId, string $template, string $title, string $body, string $type): void
    {
        if ($userId < 1) {
            return;
        }
        $this->db->insert('notifications', [
            'user_id' => $userId,
            'channel' => 'in_app',
            'template' => $template,
            'recipient' => 'app',
            'payload_json' => json_encode([
                'subject' => $title,
                'body' => $body,
                'type' => $type,
            ], JSON_UNESCAPED_UNICODE),
            'status' => 'sent',
            'sent_at' => Clock::utc(),
            'scheduled_at' => Clock::utc(),
            'created_at' => Clock::utc(),
        ]);
        $this->sendFcm($userId, $title, $body, $type);
    }

    private function sendFcm(int $userId, string $title, string $body, string $type): void
    {
        $key = trim((string) env_value('FCM_SERVER_KEY', ''));
        if ($key === '') {
            return;
        }
        $devices = $this->db->fetchAll(
            "SELECT push_token FROM api_devices WHERE user_id = :uid AND push_token IS NOT NULL AND push_token != ''",
            ['uid' => $userId]
        );
        foreach ($devices as $device) {
            $token = (string) ($device['push_token'] ?? '');
            if ($token === '' || strlen($token) < 32) {
                continue;
            }
            $this->postFcm($key, $token, $title, $body, $type);
        }
    }

    private function postFcm(string $key, string $token, string $title, string $body, string $type): void
    {
        $ch = curl_init('https://fcm.googleapis.com/fcm/send');
        if ($ch === false) {
            return;
        }
        $payload = json_encode([
            'to' => $token,
            'priority' => 'high',
            'content_available' => true,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => [
                'type' => $type,
            ],
        ], JSON_UNESCAPED_UNICODE);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: key=' . $key,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 8,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($status >= 400) {
            Logger::error('FCM se nepodařilo odeslat', [
                'status' => $status,
                'body' => is_string($raw) ? substr($raw, 0, 300) : '',
            ]);
        }
    }
}
