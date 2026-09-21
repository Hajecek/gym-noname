<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\Crypto;
use App\Core\Database;
use App\Support\Clock;

/**
 * Platební vrstva. Konkrétní brána se napojí až po výběru poskytovatele.
 * Přesměrování na success URL nikdy není důkazem platby.
 */
final class PaymentService
{
    public function __construct(private readonly Database $db)
    {
    }

    public function createManual(array $user, string $amount, ?int $reservationId = null, ?int $membershipId = null): array
    {
        $id = (int) $this->db->insert('payments', [
            'public_id' => Crypto::uuid(),
            'user_id' => (int) $user['id'],
            'reservation_id' => $reservationId,
            'membership_id' => $membershipId,
            'provider' => 'manual',
            'amount' => $amount,
            'currency' => 'CZK',
            'status' => 'pending',
            'created_at' => Clock::utc(),
            'updated_at' => Clock::utc(),
        ]);
        return $this->db->fetch('SELECT * FROM payments WHERE id = :id', ['id' => $id]) ?? [];
    }

    public function createStripeMembership(array $user, string $amount, int $membershipId): array
    {
        $id = (int) $this->db->insert('payments', [
            'public_id' => Crypto::uuid(),
            'user_id' => (int) $user['id'],
            'membership_id' => $membershipId,
            'provider' => 'stripe',
            'amount' => $amount,
            'currency' => 'CZK',
            'status' => 'pending',
            'created_at' => Clock::utc(),
            'updated_at' => Clock::utc(),
        ]);
        return $this->db->fetch('SELECT * FROM payments WHERE id = :id', ['id' => $id]) ?? [];
    }

    public function createStripeHold(array $user, string $amount, int $reservationId): array
    {
        $id = (int) $this->db->insert('payments', [
            'public_id' => Crypto::uuid(),
            'user_id' => (int) $user['id'],
            'reservation_id' => $reservationId,
            'provider' => 'stripe',
            'amount' => $amount,
            'currency' => 'CZK',
            'status' => 'pending',
            'created_at' => Clock::utc(),
            'updated_at' => Clock::utc(),
        ]);
        return $this->db->fetch('SELECT * FROM payments WHERE id = :id', ['id' => $id]) ?? [];
    }

    public function attachProviderReference(int $paymentId, string $reference): void
    {
        $this->db->update('payments', [
            'provider_reference' => $reference,
            'updated_at' => Clock::utc(),
        ], 'id = :id', ['id' => $paymentId]);
    }

    public function findByProviderReference(string $reference): ?array
    {
        return $this->db->fetch(
            'SELECT * FROM payments WHERE provider = :p AND provider_reference = :r ORDER BY id DESC LIMIT 1',
            ['p' => 'stripe', 'r' => $reference]
        );
    }

    public function findByPublicId(string $publicId): ?array
    {
        return $this->db->fetch('SELECT * FROM payments WHERE public_id = :pid', ['pid' => $publicId]);
    }

    public function markPaid(int $paymentId, string $reference, string $idempotencyKey): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM payment_events WHERE idempotency_key = :k',
            ['k' => $idempotencyKey]
        );
        if ($existing) {
            return;
        }
        try {
            $this->db->transaction(function (Database $db) use ($paymentId, $reference, $idempotencyKey): void {
                $current = $db->fetch('SELECT provider_reference FROM payments WHERE id = :id', ['id' => $paymentId]);
                $kept = (string) ($current['provider_reference'] ?? '');
                $stored = (str_starts_with($kept, 'cs_') && !str_starts_with($reference, 'cs_')) ? $kept : $reference;
                $db->update('payments', [
                    'status' => 'paid',
                    'provider_reference' => $stored,
                    'paid_at' => Clock::utc(),
                ], 'id = :id', ['id' => $paymentId]);
                $db->insert('payment_events', [
                    'payment_id' => $paymentId,
                    'event_type' => 'paid',
                    'payload_hash' => Crypto::hash($reference . $idempotencyKey),
                    'idempotency_key' => $idempotencyKey,
                    'created_at' => Clock::utc(),
                ]);
            });
        } catch (\PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }
        }
    }

    public function forUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM payments WHERE user_id = :id ORDER BY created_at DESC',
            ['id' => $userId]
        );
    }
}
