<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\Logger;
use App\Support\Clock;
use PHPMailer\PHPMailer\PHPMailer;

final class MailService
{
    public function __construct(private readonly Database $db)
    {
    }

    public function queue(string $template, string $recipient, array $payload, ?int $userId = null, ?string $scheduledAt = null): void
    {
        $this->db->insert('notifications', [
            'user_id' => $userId,
            'channel' => 'email',
            'template' => $template,
            'recipient' => $recipient,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => 'pending',
            'scheduled_at' => $scheduledAt ?? Clock::utc(),
            'created_at' => Clock::utc(),
        ]);

        if ((string) env_value('APP_ENV', 'local') === 'local') {
            $this->processPending(5);
        }
    }

    public function processPending(int $limit = 20): int
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM notifications
             WHERE channel = 'email' AND status = 'pending' AND scheduled_at <= :now
             ORDER BY id ASC LIMIT {$limit}",
            ['now' => Clock::utc()]
        );
        $sent = 0;
        foreach ($rows as $row) {
            try {
                $this->sendRow($row);
                $this->db->update('notifications', [
                    'status' => 'sent',
                    'sent_at' => Clock::utc(),
                    'last_error' => null,
                ], 'id = :id', ['id' => (int) $row['id']]);
                $sent++;
            } catch (\Throwable $e) {
                Logger::error('Odeslání e-mailu selhalo', ['id' => $row['id'], 'error' => $e->getMessage()]);
                $this->db->update('notifications', [
                    'status' => ((int) $row['attempts'] + 1) >= 5 ? 'failed' : 'pending',
                    'attempts' => (int) $row['attempts'] + 1,
                    'last_error' => substr($e->getMessage(), 0, 500),
                ], 'id = :id', ['id' => (int) $row['id']]);
            }
        }
        return $sent;
    }

    private function sendRow(array $row): void
    {
        $payload = json_decode((string) $row['payload_json'], true) ?: [];
        $html = $this->render((string) $row['template'], $payload);
        $subject = (string) ($payload['subject'] ?? 'PRIVOFIT');
        $mailer = (string) env_value('MAIL_MAILER', 'log');

        if ($mailer === 'log') {
            Logger::info('E-mail (log)', [
                'to' => $row['recipient'],
                'subject' => $subject,
                'template' => $row['template'],
            ]);
            Logger::append(
                'mail-' . gmdate('Y-m-d') . '.log',
                sprintf("[%s] TO=%s SUBJECT=%s\n%s\n\n", Clock::utc(), $row['recipient'], $subject, $html)
            );
            return;
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) env_value('MAIL_HOST');
        $mail->Port = (int) env_value('MAIL_PORT', 587);
        $mail->SMTPAuth = (string) env_value('MAIL_USERNAME', '') !== '';
        $mail->Username = (string) env_value('MAIL_USERNAME', '');
        $mail->Password = (string) env_value('MAIL_PASSWORD', '');
        $enc = (string) env_value('MAIL_ENCRYPTION', 'tls');
        if ($enc === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail->CharSet = 'UTF-8';
        $mail->setFrom((string) env_value('MAIL_FROM_ADDRESS'), (string) env_value('MAIL_FROM_NAME', 'PRIVOFIT'));
        $mail->addAddress((string) $row['recipient']);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html;
        $mail->AltBody = strip_tags($html);
        $mail->send();
    }

    private function render(string $template, array $payload): string
    {
        $path = dirname(__DIR__, 2) . '/resources/emails/' . $template . '.php';
        if (!is_file($path)) {
            $path = dirname(__DIR__, 2) . '/resources/emails/generic.php';
        }
        extract($payload, EXTR_SKIP);
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
