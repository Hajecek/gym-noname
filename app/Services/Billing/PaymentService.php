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

    public function markPaid(int $paymentId, string $reference, string $idempotencyKey): void
    {
        $existing = $this->db->fetch(
            'SELECT id FROM payment_events WHERE idempotency_key = :k',
            ['k' => $idempotencyKey]
        );
        if ($existing) {
            return;
        }
        $this->db->transaction(function (Database $db) use ($paymentId, $reference, $idempotencyKey): void {
            $db->update('payments', [
                'status' => 'paid',
                'provider_reference' => $reference,
                'paid_at' => Clock::utc(),
            ], 'id = :id', ['id' => $paymentId]);
            $db->insert('payment_events', [
                'payment_id' => $paymentId,
                'event_type' => 'paid',
                'payload_hash' => Crypto::hash($reference . $idempotencyKey),
                'idempotency_key' => $idempotencyKey,
                'created_at' => Clock::utc(),
            ]);
            $payment = $db->fetch('SELECT * FROM payments WHERE id = :id', ['id' => $paymentId]);
            if ($payment && $payment['reservation_id']) {
                $db->update('reservations', ['status' => 'confirmed'], 'id = :id AND status = :pending', [
                    'id' => (int) $payment['reservation_id'],
                    'pending' => 'pending_payment',
                ]);
            }
        });
    }

    public function forUser(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM payments WHERE user_id = :id ORDER BY created_at DESC',
            ['id' => $userId]
        );
    }
}
