<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Services\Access\AccessControlService;
use App\Services\Auth\AuthService;
use App\Services\Billing\PaymentService;
use App\Services\Billing\StripeGateway;
use App\Services\Content\ContentService;
use App\Services\MembershipService;
use App\Services\ReservationService;
use App\Support\Clock;

final class MobileApiService
{
    public function __construct(
        private readonly Database $db,
        private readonly AuthService $auth,
        private readonly ReservationService $reservations,
        private readonly MembershipService $memberships,
        private readonly AccessControlService $access,
        private readonly ContentService $content,
        private readonly PaymentService $payments,
    ) {
    }

    public static function make(Database $db): self
    {
        return new self(
            $db,
            AuthService::make($db),
            ReservationService::make($db),
            new MembershipService($db),
            AccessControlService::make($db),
            new ContentService($db),
            new PaymentService($db),
        );
    }

    public function sessionPayload(array $tokens): array
    {
        $expires = Clock::nowUtc()->modify('+' . (int) $tokens['expires_in'] . ' seconds');
        return [
            'accessToken' => $tokens['access_token'],
            'refreshToken' => $tokens['refresh_token'],
            'expiresAt' => Clock::iso($expires->format('Y-m-d H:i:s')),
        ];
    }

    public function memberPayload(array $user): array
    {
        $status = match ((string) $user['status']) {
            'active' => 'active',
            'blocked' => 'blocked',
            default => 'inactive',
        };
        return [
            'id' => $user['public_id'],
            'firstName' => $user['first_name'],
            'username' => $user['username'],
            'email' => $user['email'],
            'status' => $status,
        ];
    }

    public function issueSession(array $user, Request $request): array
    {
        $platform = strtolower(trim((string) $request->input('platform', 'ios')));
        if (!in_array($platform, ['ios', 'android', 'web', 'other'], true)) {
            $platform = 'ios';
        }
        $deviceName = trim((string) $request->input('deviceName', $request->input('device_name', 'iOS')));
        if ($deviceName === '') {
            $deviceName = 'iOS';
        }
        return $this->sessionPayload($this->auth->issueApiTokens($user, $request, $deviceName, $platform));
    }

    public function gymInfo(): array
    {
        $hero = $this->content->page('home.hero', 'PRIVOFIT', 'Soukromé fitness studio.');
        $contact = $this->content->contact();
        $announcements = [];
        foreach ($this->content->faqs() as $faq) {
            $announcements[] = (string) $faq['question'];
            if (count($announcements) >= 3) {
                break;
            }
        }
        return [
            'name' => (string) config('app.name', 'PRIVOFIT'),
            'description' => trim(strip_tags((string) ($hero['body_html'] ?? ''))),
            'openingHours' => (string) ($contact['hours'] ?? 'Podle rezervací'),
            'announcements' => $announcements,
        ];
    }

    public function offers(): array
    {
        $offers = [];
        foreach ($this->memberships->plans() as $plan) {
            $price = number_format((float) $plan['price'], 0, ',', ' ') . ' ' . ($plan['currency'] ?? 'Kč');
            $offers[] = [
                'id' => $plan['public_id'],
                'name' => $plan['name'],
                'description' => (string) ($plan['description'] ?? ''),
                'priceDescription' => $price,
            ];
        }
        return $offers;
    }

    public function membership(array $user): array
    {
        $row = $this->memberships->activeForUser((int) $user['id']);
        if (!$row) {
            $now = Clock::nowUtc();
            return [
                'title' => 'Bez členství',
                'validUntil' => Clock::iso($now->format('Y-m-d H:i:s')),
                'remainingEntries' => 0,
                'isActive' => false,
                'validFrom' => null,
                'status' => 'inactive',
            ];
        }
        $validUntil = $row['ends_at']
            ? Clock::iso($row['ends_at'])
            : Clock::iso(Clock::nowUtc()->modify('+10 years')->format('Y-m-d H:i:s'));
        $status = 'active';
        if ($row['ends_at']) {
            $endsAt = new \DateTimeImmutable($row['ends_at'], new \DateTimeZone('UTC'));
            if ($endsAt <= Clock::nowUtc()->modify('+14 days')) {
                $status = 'ending';
            }
        }
        return [
            'title' => (string) ($row['plan_name'] ?? 'Členství'),
            'validUntil' => $validUntil,
            'remainingEntries' => $row['entries_remaining'] === null ? null : (int) $row['entries_remaining'],
            'isActive' => true,
            'validFrom' => $row['starts_at'] ? Clock::iso($row['starts_at']) : null,
            'status' => $status,
        ];
    }

    public function reservations(array $user): array
    {
        $items = [];
        foreach ($this->reservations->forUser((int) $user['id']) as $row) {
            if (!in_array($row['status'], ['confirmed', 'pending_payment'], true)) {
                continue;
            }
            $items[] = $this->reservationPayload($row, $user);
        }
        return $items;
    }

    public function visits(array $user): array
    {
        $items = [];
        foreach ($this->reservations->forUser((int) $user['id']) as $row) {
            if ($row['status'] !== 'completed') {
                continue;
            }
            $items[] = [
                'id' => $row['public_id'],
                'date' => Clock::iso($row['starts_at']),
                'room' => (string) ($row['room_name'] ?? 'Studio'),
            ];
        }
        return $items;
    }

    public function slots(): array
    {
        return $this->reservations->availableSlotsForApp(14);
    }

    public function quote(array $user, array $slotIds): array
    {
        $resolved = $this->reservations->resolveSlotIds($slotIds);
        $price = $this->reservations->slotPrice();
        $membership = $this->memberships->activeForUser((int) $user['id']);
        $covered = $membership && (
            $membership['entries_remaining'] === null
            || (int) $membership['entries_remaining'] >= count($resolved)
        );
        if ($covered) {
            $price = '0.00';
        }
        return [
            'slots' => $resolved,
            'pricePerSlot' => $price,
            'currencyCode' => 'CZK',
        ];
    }

    public function reserve(array $user, string $slotId, string $requestId): array
    {
        if ($cached = $this->idempotent($user, $requestId)) {
            return $cached;
        }
        $reservation = $this->reservations->createFromSlotId($user, $slotId, false);
        $payload = $this->reservationPayload($reservation, $user);
        $this->storeIdempotent($user, $requestId, 'reservations', $payload);
        return $payload;
    }

    public function cancel(array $user, string $id, string $requestId): array
    {
        if ($cached = $this->idempotent($user, $requestId)) {
            return $cached;
        }
        $this->reservations->cancel($user, $id);
        $payload = ['ok' => true];
        $this->storeIdempotent($user, $requestId, 'reservations.cancel', $payload);
        return $payload;
    }

    public function payAndReserve(array $user, array $slotIds, string $requestId, array $applePay): array
    {
        if ($cached = $this->idempotent($user, $requestId)) {
            return $cached;
        }
        $existing = $this->db->fetch(
            'SELECT * FROM payments WHERE user_id = :uid AND client_request_id = :rid',
            ['uid' => (int) $user['id'], 'rid' => $requestId]
        );
        if ($existing && $existing['status'] === 'paid') {
            $payload = $this->paymentPayloadFromRow($existing, $user);
            $this->storeIdempotent($user, $requestId, 'reservations.pay', $payload);
            return $payload;
        }

        $quote = $this->quote($user, $slotIds);
        $count = count($quote['slots']);
        $unit = (float) $quote['pricePerSlot'];
        $total = number_format($unit * $count, 2, '.', '');
        $chargeCard = $unit > 0;
        $holds = [];
        try {
            $this->reservations->releasePendingForSlots($user, $slotIds);
            $ignoreIds = [];
            foreach ($slotIds as $slotId) {
                $hold = $this->reservations->createFromSlotId($user, (string) $slotId, true, $ignoreIds);
                $ignoreIds[] = (int) $hold['id'];
                $holds[] = $hold;
            }
            $reference = null;
            if ($chargeCard) {
                $amountMinor = (int) round(((float) $total) * 100);
                $gateway = StripeGateway::fromConfig();
                $paymentData = $this->applePayJson($applePay);
                $charge = $paymentData === null
                    ? $gateway->chargeTestCard($amountMinor, 'czk', $requestId, 'PRIVOFIT rezervace (local test)')
                    : $gateway->chargeApplePay($paymentData, $amountMinor, 'czk', $requestId, 'PRIVOFIT rezervace');
                $reference = $charge['id'];
            } else {
                $membership = $this->memberships->activeForUser((int) $user['id']);
                if ($membership && $membership['entries_remaining'] !== null) {
                    for ($i = 0; $i < $count; $i++) {
                        $this->memberships->consumeEntry((int) $membership['id'], (int) $user['id']);
                    }
                }
            }

            $confirmed = [];
            foreach ($holds as $hold) {
                $confirmed[] = $this->reservations->confirmPending($hold, $user);
            }
            $firstId = $confirmed[0]['id'] ?? null;
            $paymentId = (int) $this->db->insert('payments', [
                'public_id' => Crypto::uuid(),
                'client_request_id' => $requestId,
                'user_id' => (int) $user['id'],
                'reservation_id' => $firstId,
                'provider' => $chargeCard ? 'stripe' : 'membership',
                'provider_reference' => $reference,
                'amount' => $total,
                'currency' => 'CZK',
                'status' => 'paid',
                'metadata_json' => json_encode(['reservations' => array_column($confirmed, 'public_id')], JSON_UNESCAPED_UNICODE),
                'paid_at' => Clock::utc(),
                'created_at' => Clock::utc(),
                'updated_at' => Clock::utc(),
            ]);
            if ($reference) {
                $this->payments->markPaid($paymentId, $reference, $requestId);
            }
            $payload = [
                'id' => $requestId,
                'status' => 'paid',
                'checkoutURL' => null,
                'reservations' => array_map(fn (array $row): array => $this->reservationPayload($row, $user), $confirmed),
            ];
            $this->storeIdempotent($user, $requestId, 'reservations.pay', $payload);
            return $payload;
        } catch (\Throwable $e) {
            foreach ($holds as $hold) {
                $this->reservations->failPending($hold);
            }
            throw $e;
        }
    }

    public function inbox(array $user): array
    {
        $rows = $this->db->fetchAll(
            'SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 40',
            ['uid' => (int) $user['id']]
        );
        $items = [];
        foreach ($rows as $row) {
            $payload = json_decode((string) $row['payload_json'], true) ?: [];
            $items[] = [
                'id' => (string) $row['id'],
                'title' => (string) ($payload['subject'] ?? $row['template']),
                'body' => (string) ($payload['body'] ?? $payload['subject'] ?? 'Aktualizace k tvému účtu PRIVOFIT.'),
                'date' => Clock::iso($row['created_at']),
            ];
        }
        return $items;
    }

    public function eligibility(array $user): array
    {
        $auth = $this->access->canAttempt($user);
        $door = $this->db->fetch('SELECT * FROM doors WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        $reservation = $auth['reservation'] ?? null;
        $until = Clock::nowUtc()->modify('+2 minutes');
        if ($reservation) {
            $late = 10;
            $until = (new \DateTimeImmutable($reservation['ends_at'], new \DateTimeZone('UTC')))->modify('+' . $late . ' minutes');
        }
        $reason = null;
        if (!$auth['allowed']) {
            $reason = ($auth['reasons'][0] ?? null) === 'unverified'
                ? 'Nejprve ověř e-mail.'
                : (($auth['reservation'] ?? null) ? 'Účet nemá oprávnění ke vstupu.' : 'Nemáš právě platnou rezervaci.');
        }
        return [
            'allowed' => (bool) $auth['allowed'],
            'reason' => $reason,
            'doorID' => $door['public_id'] ?? 'door',
            'expiresAt' => Clock::iso($until->format('Y-m-d H:i:s')),
            'doorName' => $door['name'] ?? 'Vstupní dveře',
        ];
    }

    public function openDoor(array $user, string $doorId, string $requestId, string $ip): array
    {
        $existing = $this->db->fetch(
            'SELECT * FROM door_commands WHERE user_id = :uid AND request_id = :rid',
            ['uid' => (int) $user['id'], 'rid' => $requestId]
        );
        if ($existing) {
            return $this->doorReceipt($existing);
        }
        try {
            $result = $this->db->transaction(function () use ($user, $ip) {
                return $this->access->open($user, $ip);
            });
            $outcome = !empty($result['physical_open_confirmed']) ? 'confirmedOpen' : 'accepted';
            $message = (string) ($result['message'] ?? '');
        } catch (HttpException $e) {
            $outcome = 'denied';
            $message = $e->getMessage();
            $result = [];
        }
        $door = $this->db->fetch('SELECT * FROM doors WHERE public_id = :pid OR public_id = :pid2 LIMIT 1', [
            'pid' => $doorId,
            'pid2' => $doorId,
        ]) ?: $this->db->fetch('SELECT * FROM doors WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        $entry = null;
        if ($outcome !== 'denied') {
            $current = $this->reservations->current((int) $user['id']);
            if ($current) {
                $entry = Clock::iso($current['ends_at']);
            }
        }
        $operationId = Crypto::uuid();
        $this->db->insert('door_commands', [
            'public_id' => $operationId,
            'user_id' => (int) $user['id'],
            'request_id' => $requestId,
            'door_id' => $door['id'] ?? null,
            'outcome' => $outcome,
            'message' => $message,
            'entry_until' => $entry ? (new \DateTimeImmutable($current['ends_at'], new \DateTimeZone('UTC')))->format('Y-m-d H:i:s') : null,
            'created_at' => Clock::utc(),
        ]);
        return [
            'operationID' => $operationId,
            'outcome' => $outcome,
            'message' => $message !== '' ? $message : null,
            'entryUntil' => $entry,
        ];
    }

    public function doorStatus(array $user, string $requestId, ?string $operationId): array
    {
        $row = $this->db->fetch(
            'SELECT * FROM door_commands WHERE user_id = :uid AND request_id = :rid',
            ['uid' => (int) $user['id'], 'rid' => $requestId]
        );
        if (!$row && $operationId) {
            $row = $this->db->fetch(
                'SELECT * FROM door_commands WHERE user_id = :uid AND public_id = :pid',
                ['uid' => (int) $user['id'], 'pid' => $operationId]
            );
        }
        if (!$row) {
            throw new HttpException(404, 'Příkaz ke dveřím nebyl nalezen.');
        }
        return $this->doorReceipt($row);
    }

    public function registerPush(array $user, Request $request, string $token, array $preferences): void
    {
        $payload = Crypto::verifyPayload((string) $request->bearerToken());
        $deviceId = isset($payload['did']) ? (int) $payload['did'] : 0;
        if ($deviceId < 1) {
            return;
        }
        $this->db->update('api_devices', [
            'push_token' => $token,
            'push_preferences_json' => json_encode($preferences, JSON_UNESCAPED_UNICODE),
            'last_seen_at' => Clock::utc(),
        ], 'id = :id AND user_id = :uid', [
            'id' => $deviceId,
            'uid' => (int) $user['id'],
        ]);
    }

    public function revokeCurrentDevice(Request $request): void
    {
        $payload = Crypto::verifyPayload((string) $request->bearerToken());
        if (!$payload || empty($payload['did'])) {
            return;
        }
        $this->db->update(
            'api_refresh_tokens',
            ['revoked_at' => Clock::utc()],
            'device_id = :did AND revoked_at IS NULL',
            ['did' => (int) $payload['did']]
        );
    }

    private function reservationPayload(array $row, array $user): array
    {
        if (empty($row['room_name'])) {
            $room = $this->db->fetch('SELECT name FROM rooms WHERE id = :id', ['id' => (int) $row['room_id']]);
            $row['room_name'] = $room['name'] ?? 'Studio';
        }
        $hours = $this->db->fetchColumn(
            "SELECT setting_value FROM app_settings WHERE setting_key = 'reservation.cancellation_hours'"
        );
        $limitHours = (int) ($hours ?: 12);
        $starts = new \DateTimeImmutable($row['starts_at'], new \DateTimeZone('UTC'));
        $canCancel = in_array($row['status'], ['confirmed', 'pending_payment'], true)
            && $starts->modify('-' . $limitHours . ' hours') >= Clock::nowUtc();
        return [
            'id' => $row['public_id'],
            'start' => Clock::iso($row['starts_at']),
            'end' => Clock::iso($row['ends_at']),
            'room' => (string) $row['room_name'],
            'canCancel' => $canCancel,
        ];
    }

    private function doorReceipt(array $row): array
    {
        return [
            'operationID' => $row['public_id'],
            'outcome' => $row['outcome'],
            'message' => $row['message'] !== null && $row['message'] !== '' ? $row['message'] : null,
            'entryUntil' => $row['entry_until'] ? Clock::iso($row['entry_until']) : null,
        ];
    }

    private function applePayJson(array $applePay): ?string
    {
        $raw = (string) ($applePay['paymentData'] ?? '');
        $decoded = base64_decode($raw, true);
        $json = $decoded !== false ? $decoded : $raw;
        $parsed = json_decode($json, true);
        $looksLikeApplePay = is_array($parsed)
            && isset($parsed['data'], $parsed['signature'], $parsed['header']);
        if (!$looksLikeApplePay) {
            if ($this->allowLocalStripeTest()) {
                return null;
            }
            throw new HttpException(422, 'Chybí Apple Pay token.');
        }
        return $json;
    }

    private function allowLocalStripeTest(): bool
    {
        $env = (string) env_value('APP_ENV', 'production');
        $key = trim((string) env_value('STRIPE_SECRET_KEY', ''));
        return $env === 'local' && str_starts_with($key, 'sk_test_');
    }

    private function paymentPayloadFromRow(array $payment, array $user): array
    {
        $meta = json_decode((string) ($payment['metadata_json'] ?? ''), true) ?: [];
        $ids = $meta['reservations'] ?? [];
        $reservations = [];
        foreach ($ids as $id) {
            $row = $this->db->fetch(
                'SELECT r.*, rm.name AS room_name FROM reservations r INNER JOIN rooms rm ON rm.id = r.room_id WHERE r.public_id = :pid',
                ['pid' => $id]
            );
            if ($row) {
                $reservations[] = $this->reservationPayload($row, $user);
            }
        }
        return [
            'id' => $payment['client_request_id'] ?: $payment['public_id'],
            'status' => $payment['status'] === 'paid' ? 'paid' : ($payment['status'] === 'failed' ? 'failed' : 'pending'),
            'checkoutURL' => null,
            'reservations' => $reservations,
        ];
    }

    private function idempotent(array $user, string $key): ?array
    {
        if ($key === '') {
            return null;
        }
        $row = $this->db->fetch(
            'SELECT response_json FROM api_idempotency WHERE user_id = :uid AND idempotency_key = :k',
            ['uid' => (int) $user['id'], 'k' => $key]
        );
        if (!$row) {
            return null;
        }
        $decoded = json_decode((string) $row['response_json'], true);
        return is_array($decoded) ? $decoded : null;
    }

    private function storeIdempotent(array $user, string $key, string $route, array $payload): void
    {
        if ($key === '') {
            return;
        }
        try {
            $this->db->insert('api_idempotency', [
                'user_id' => (int) $user['id'],
                'idempotency_key' => $key,
                'route' => $route,
                'status_code' => 200,
                'response_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => Clock::utc(),
            ]);
        } catch (\Throwable) {
            // duplicitní klíč – první odpověď zůstává
        }
    }
}
