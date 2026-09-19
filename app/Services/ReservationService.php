<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\HttpException;
use App\Support\Clock;

final class ReservationService
{
    public function __construct(
        private readonly Database $db,
        private readonly SettingsService $settings,
        private readonly MembershipService $memberships,
        private readonly MailService $mail,
    ) {
    }

    public static function make(Database $db): self
    {
        $settings = new SettingsService($db);
        return new self($db, $settings, new MembershipService($db), new MailService($db));
    }

    public function availability(string $localDate, ?int $roomId = null): array
    {
        $room = $this->room($roomId);
        $slotMinutes = $this->settings->int('reservation.slot_minutes', 60);
        $minMinutes = $this->settings->int('reservation.min_minutes', 60);
        $maxMinutes = $this->settings->int('reservation.max_minutes', 180);
        $buffer = $this->settings->int('reservation.buffer_minutes', 15);
        $maxPersons = (int) $room['max_persons'];

        $day = Clock::parseLocal($localDate . ' 00:00:00');
        $hours = $this->hoursForDate($room, $day);
        if ($hours['closed']) {
            return [
                'date' => $localDate,
                'closed' => true,
                'slots' => [],
                'slot_minutes' => $slotMinutes,
                'min_minutes' => $minMinutes,
                'max_minutes' => $maxMinutes,
                'buffer_minutes' => $buffer,
                'max_persons' => $maxPersons,
            ];
        }

        $open = Clock::parseLocal($localDate . ' ' . $hours['opens_at']);
        $close = Clock::parseLocal($localDate . ' ' . $hours['closes_at']);
        $nowLocal = Clock::nowLocal();
        $occupied = $this->occupiedIntervals((int) $room['id'], Clock::toUtc($open)->format('Y-m-d H:i:s'), Clock::toUtc($close)->format('Y-m-d H:i:s'));

        $slots = [];
        for ($cursor = $open; $cursor < $close; $cursor = $cursor->modify('+' . $slotMinutes . ' minutes')) {
            $end = $cursor->modify('+' . $minMinutes . ' minutes');
            if ($end > $close) {
                break;
            }
            $utcStart = Clock::toUtc($cursor);
            $available = $utcStart > Clock::nowUtc() && !$this->overlaps($occupied, $utcStart, Clock::toUtc($end), $buffer);
            $slots[] = [
                'start' => $cursor->format('H:i'),
                'start_at' => $utcStart->format('Y-m-d H:i:s'),
                'available' => $available,
            ];
        }

        return [
            'date' => $localDate,
            'closed' => false,
            'slots' => $slots,
            'slot_minutes' => $slotMinutes,
            'min_minutes' => $minMinutes,
            'max_minutes' => $maxMinutes,
            'buffer_minutes' => $buffer,
            'max_persons' => $maxPersons,
            'now' => $nowLocal->format('Y-m-d H:i:s'),
        ];
    }

    public function create(array $user, string $localStart, int $durationMinutes, int $guestCount, ?int $roomId = null): array
    {
        if (empty($user['email_verified_at'])) {
            throw new HttpException(403, 'Nejprve ověřte e-mailovou adresu.');
        }
        if ($user['status'] !== 'active') {
            throw new HttpException(403, 'Účet není aktivní.');
        }

        $room = $this->room($roomId);
        $min = $this->settings->int('reservation.min_minutes', 60);
        $max = $this->settings->int('reservation.max_minutes', 180);
        $buffer = $this->settings->int('reservation.buffer_minutes', 15);
        $slot = $this->settings->int('reservation.slot_minutes', 60);
        if ($durationMinutes < $min || $durationMinutes > $max || $durationMinutes % $slot !== 0) {
            throw new HttpException(422, 'Neplatná délka rezervace.');
        }
        if ($guestCount < 1 || $guestCount > (int) $room['max_persons']) {
            throw new HttpException(422, 'Neplatný počet osob.');
        }

        $startLocal = Clock::parseLocal($localStart);
        $endLocal = $startLocal->modify('+' . $durationMinutes . ' minutes');
        $startUtc = Clock::toUtc($startLocal);
        $endUtc = Clock::toUtc($endLocal);

        if ($startUtc <= Clock::nowUtc()) {
            throw new HttpException(422, 'Nelze rezervovat termín v minulosti.');
        }

        $hours = $this->hoursForDate($room, $startLocal);
        if ($hours['closed']) {
            throw new HttpException(422, 'Fitko je v tento den zavřené.');
        }
        $open = Clock::parseLocal($startLocal->format('Y-m-d') . ' ' . $hours['opens_at']);
        $close = Clock::parseLocal($startLocal->format('Y-m-d') . ' ' . $hours['closes_at']);
        if ($startLocal < $open || $endLocal > $close) {
            throw new HttpException(422, 'Termín je mimo provozní dobu.');
        }

        $price = $this->priceForDuration($durationMinutes);
        $membership = $this->memberships->activeForUser((int) $user['id']);
        $useMembership = $membership && ($membership['entries_remaining'] === null || (int) $membership['entries_remaining'] > 0);

        return $this->db->transaction(function (Database $db) use ($room, $user, $startUtc, $endUtc, $buffer, $guestCount, $price, $membership, $useMembership) {
            $db->query('SELECT id FROM rooms WHERE id = :id FOR UPDATE', ['id' => (int) $room['id']]);
            $occupied = $this->occupiedIntervals((int) $room['id'], $startUtc->modify('-6 hours')->format('Y-m-d H:i:s'), $endUtc->modify('+6 hours')->format('Y-m-d H:i:s'));
            if ($this->overlaps($occupied, $startUtc, $endUtc, $buffer)) {
                throw new HttpException(409, 'Tento termín je již obsazený.');
            }

            $status = $useMembership ? 'confirmed' : 'pending_payment';
            $membershipId = $useMembership ? (int) $membership['id'] : null;
            if ($useMembership && $membership['entries_remaining'] !== null) {
                $this->memberships->consumeEntry((int) $membership['id'], (int) $user['id']);
            }

            $id = (int) $db->insert('reservations', [
                'public_id' => Crypto::uuid(),
                'room_id' => (int) $room['id'],
                'user_id' => (int) $user['id'],
                'membership_id' => $membershipId,
                'status' => $status,
                'starts_at' => $startUtc->format('Y-m-d H:i:s'),
                'ends_at' => $endUtc->format('Y-m-d H:i:s'),
                'buffer_minutes' => $buffer,
                'guest_count' => $guestCount,
                'price' => $useMembership ? 0 : $price,
                'currency' => 'CZK',
                'created_at' => Clock::utc(),
                'updated_at' => Clock::utc(),
            ]);

            $door = $db->fetch('SELECT id FROM doors WHERE room_id = :rid AND is_active = 1 LIMIT 1', ['rid' => (int) $room['id']]);
            if ($door && $status === 'confirmed') {
                $early = $this->settings->int('access.early_minutes', 10);
                $late = $this->settings->int('access.late_minutes', 10);
                $db->insert('access_permissions', [
                    'user_id' => (int) $user['id'],
                    'door_id' => (int) $door['id'],
                    'reservation_id' => $id,
                    'valid_from' => $startUtc->modify('-' . $early . ' minutes')->format('Y-m-d H:i:s'),
                    'valid_until' => $endUtc->modify('+' . $late . ' minutes')->format('Y-m-d H:i:s'),
                    'created_at' => Clock::utc(),
                ]);
            }

            $reservation = $db->fetch('SELECT * FROM reservations WHERE id = :id', ['id' => $id]);
            if ($status === 'confirmed') {
                $this->mail->queue('reservation-confirmed', $user['email'], [
                    'subject' => 'Potvrzení rezervace PRIVOFIT',
                    'first_name' => $user['first_name'],
                    'starts_at' => Clock::format($reservation['starts_at']),
                    'ends_at' => Clock::format($reservation['ends_at']),
                ], (int) $user['id']);
            }
            return $reservation;
        });
    }

    public function cancel(array $user, string $publicId, bool $admin = false): void
    {
        $reservation = $this->owned($user, $publicId, $admin);
        if (!in_array($reservation['status'], ['pending_payment', 'confirmed'], true)) {
            throw new HttpException(422, 'Tuto rezervaci nelze zrušit.');
        }
        $hours = $this->settings->int('reservation.cancellation_hours', 12);
        $starts = new \DateTimeImmutable($reservation['starts_at'], new \DateTimeZone('UTC'));
        if (!$admin && $starts->modify('-' . $hours . ' hours') < Clock::nowUtc()) {
            throw new HttpException(422, 'Storno je možné nejpozději ' . $hours . ' hodin před začátkem.');
        }
        $this->db->update('reservations', [
            'status' => 'cancelled',
            'cancelled_at' => Clock::utc(),
            'cancelled_by' => (int) $user['id'],
        ], 'id = :id', ['id' => (int) $reservation['id']]);
        $this->db->query('DELETE FROM access_permissions WHERE reservation_id = :id', ['id' => (int) $reservation['id']]);
        $this->mail->queue('reservation-cancelled', $user['email'] ?? $this->db->fetch('SELECT email, first_name FROM users WHERE id = :id', ['id' => (int) $reservation['user_id']])['email'], [
            'subject' => 'Zrušení rezervace PRIVOFIT',
            'first_name' => $user['first_name'],
            'starts_at' => Clock::format($reservation['starts_at']),
        ], (int) $reservation['user_id']);
    }

    public function owned(array $user, string $publicId, bool $admin = false): array
    {
        $reservation = $this->db->fetch('SELECT * FROM reservations WHERE public_id = :pid', ['pid' => $publicId]);
        if (!$reservation) {
            throw new HttpException(404, 'Rezervace nebyla nalezena.');
        }
        if (!$admin && (int) $reservation['user_id'] !== (int) $user['id']) {
            throw new HttpException(403, 'K této rezervaci nemáte přístup.');
        }
        return $reservation;
    }

    public function forUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT r.*, rm.name AS room_name
             FROM reservations r
             INNER JOIN rooms rm ON rm.id = r.room_id
             WHERE r.user_id = :uid
             ORDER BY r.starts_at DESC',
            ['uid' => $userId]
        );
    }

    public function upcoming(int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT r.*, rm.name AS room_name
             FROM reservations r
             INNER JOIN rooms rm ON rm.id = r.room_id
             WHERE r.user_id = :uid AND r.status = 'confirmed' AND r.ends_at >= :now
             ORDER BY r.starts_at ASC LIMIT 1",
            ['uid' => $userId, 'now' => Clock::utc()]
        );
    }

    public function current(int $userId): ?array
    {
        $early = $this->settings->int('access.early_minutes', 10);
        return $this->db->fetch(
            "SELECT r.*, rm.name AS room_name
             FROM reservations r
             INNER JOIN rooms rm ON rm.id = r.room_id
             WHERE r.user_id = :uid AND r.status = 'confirmed'
               AND DATE_SUB(r.starts_at, INTERVAL {$early} MINUTE) <= :now
               AND r.ends_at >= :now2
             ORDER BY r.starts_at ASC LIMIT 1",
            ['uid' => $userId, 'now' => Clock::utc(), 'now2' => Clock::utc()]
        );
    }

    public function expireHolds(): int
    {
        $minutes = $this->settings->int('reservation.hold_minutes', 15);
        $limit = Clock::nowUtc()->modify('-' . $minutes . ' minutes')->format('Y-m-d H:i:s');
        $rows = $this->db->fetchAll(
            "SELECT id FROM reservations WHERE status = 'pending_payment' AND created_at < :limit",
            ['limit' => $limit]
        );
        foreach ($rows as $row) {
            $this->db->update('reservations', ['status' => 'expired'], 'id = :id', ['id' => (int) $row['id']]);
        }
        $this->db->query(
            "UPDATE reservations SET status = 'completed'
             WHERE status = 'confirmed' AND ends_at < :now",
            ['now' => Clock::utc()]
        );
        return count($rows);
    }

    public function today(int $roomId): array
    {
        $start = Clock::nowLocal()->setTime(0, 0);
        $end = $start->modify('+1 day');
        return $this->db->fetchAll(
            "SELECT r.*, u.first_name, u.last_name, u.username, u.phone
             FROM reservations r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.room_id = :rid AND r.status IN ('confirmed', 'pending_payment')
               AND r.starts_at >= :start AND r.starts_at < :end
             ORDER BY r.starts_at ASC",
            [
                'rid' => $roomId,
                'start' => Clock::toUtc($start)->format('Y-m-d H:i:s'),
                'end' => Clock::toUtc($end)->format('Y-m-d H:i:s'),
            ]
        );
    }

    public function room(?int $roomId = null): array
    {
        $room = $roomId
            ? $this->db->fetch('SELECT * FROM rooms WHERE id = :id AND is_active = 1', ['id' => $roomId])
            : $this->db->fetch('SELECT * FROM rooms WHERE is_active = 1 ORDER BY id ASC LIMIT 1');
        if (!$room) {
            throw new HttpException(404, 'Prostor nebyl nalezen.');
        }
        return $room;
    }

    public function occupancyNow(): array
    {
        $room = $this->room();
        $current = $this->db->fetch(
            "SELECT r.*, u.first_name, u.last_name
             FROM reservations r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.room_id = :rid AND r.status = 'confirmed' AND r.starts_at <= :now AND r.ends_at >= :now2
             LIMIT 1",
            ['rid' => (int) $room['id'], 'now' => Clock::utc(), 'now2' => Clock::utc()]
        );
        return [
            'occupied' => $current !== null,
            'reservation' => $current,
        ];
    }

    private function hoursForDate(array $room, \DateTimeImmutable $localDay): array
    {
        $date = $localDay->format('Y-m-d');
        $exception = $this->db->fetch(
            'SELECT * FROM opening_hour_exceptions WHERE room_id = :rid AND exception_date = :d',
            ['rid' => (int) $room['id'], 'd' => $date]
        );
        if ($exception) {
            return [
                'closed' => (int) $exception['is_closed'] === 1,
                'opens_at' => $exception['opens_at'] ?? '06:00:00',
                'closes_at' => $exception['closes_at'] ?? '22:00:00',
            ];
        }
        $weekday = (int) $localDay->format('N');
        $hours = $this->db->fetch(
            'SELECT * FROM opening_hours WHERE room_id = :rid AND weekday = :w',
            ['rid' => (int) $room['id'], 'w' => $weekday]
        );
        if (!$hours || (int) $hours['is_closed'] === 1) {
            return ['closed' => true, 'opens_at' => '00:00:00', 'closes_at' => '00:00:00'];
        }
        return [
            'closed' => false,
            'opens_at' => $hours['opens_at'],
            'closes_at' => $hours['closes_at'],
        ];
    }

    private function occupiedIntervals(int $roomId, string $from, string $to): array
    {
        $reservations = $this->db->fetchAll(
            "SELECT starts_at, ends_at, buffer_minutes
             FROM reservations
             WHERE room_id = :rid AND status IN ('pending_payment', 'confirmed')
               AND starts_at < :to AND ends_at > :from",
            ['rid' => $roomId, 'from' => $from, 'to' => $to]
        );
        $blocks = $this->db->fetchAll(
            'SELECT starts_at, ends_at, 0 AS buffer_minutes FROM blocked_slots
             WHERE room_id = :rid AND starts_at < :to AND ends_at > :from',
            ['rid' => $roomId, 'from' => $from, 'to' => $to]
        );
        return array_merge($reservations, $blocks);
    }

    private function overlaps(array $occupied, \DateTimeImmutable $start, \DateTimeImmutable $end, int $buffer): bool
    {
        foreach ($occupied as $item) {
            $existingStart = new \DateTimeImmutable($item['starts_at'], new \DateTimeZone('UTC'));
            $existingEnd = (new \DateTimeImmutable($item['ends_at'], new \DateTimeZone('UTC')))
                ->modify('+' . (int) ($item['buffer_minutes'] ?? 0) . ' minutes');
            $newEnd = $end->modify('+' . $buffer . ' minutes');
            if ($start < $existingEnd && $newEnd > $existingStart) {
                return true;
            }
        }
        return false;
    }

    private function priceForDuration(int $minutes): string
    {
        $hourly = (float) $this->settings->get('pricing.hourly', 249);
        $hours = max(1, (int) ceil($minutes / 60));
        return number_format($hourly * $hours, 2, '.', '');
    }
}
