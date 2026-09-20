<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Core\HttpException;

/**
 * Stripe Test/Live přes REST. Tajný klíč zůstává na serveru.
 */
final class StripeGateway
{
    public function __construct(private readonly string $secretKey)
    {
    }

    public static function fromConfig(): self
    {
        $key = trim((string) env_value('STRIPE_SECRET_KEY', ''));
        if ($key === '' || !str_starts_with($key, 'sk_')) {
            throw new HttpException(503, 'Stripe není nakonfigurovaný.');
        }
        return new self($key);
    }

    public function configured(): bool
    {
        return str_starts_with($this->secretKey, 'sk_');
    }

    /**
     * @return array{id:string,status:string}
     */
    public function chargeApplePay(string $paymentDataJson, int $amountMinor, string $currency, string $idempotencyKey, string $description): array
    {
        $this->assertAmount($amountMinor);
        $token = $this->createApplePayToken($paymentDataJson, $idempotencyKey . ':token');
        return $this->confirmIntent([
            'amount' => (string) $amountMinor,
            'currency' => strtolower($currency),
            'confirm' => 'true',
            'off_session' => 'true',
            'confirmation_method' => 'automatic',
            'description' => $description,
            'payment_method_data[type]' => 'card',
            'payment_method_data[card][token]' => $token,
        ], $idempotencyKey . ':pi');
    }

    /**
     * Simulátor iOS nedoručí skutečný Apple Pay JSON. V APP_ENV=local + sk_test
     * se účtuje Stripe test karta, ne falešný úspěch bez Stripe.
     *
     * @return array{id:string,status:string}
     */
    public function chargeTestCard(int $amountMinor, string $currency, string $idempotencyKey, string $description): array
    {
        if (!str_starts_with($this->secretKey, 'sk_test_')) {
            throw new HttpException(422, 'Chybí Apple Pay token.');
        }
        $this->assertAmount($amountMinor);
        return $this->confirmIntent([
            'amount' => (string) $amountMinor,
            'currency' => strtolower($currency),
            'confirm' => 'true',
            'off_session' => 'true',
            'confirmation_method' => 'automatic',
            'description' => $description,
            'payment_method' => 'pm_card_visa',
            'payment_method_types' => ['card'],
        ], $idempotencyKey . ':test-pi');
    }

    private function assertAmount(int $amountMinor): void
    {
        if ($amountMinor < 1) {
            throw new HttpException(422, 'Neplatná částka.');
        }
    }

    /** @param array<string, mixed> $fields @return array{id:string,status:string} */
    private function confirmIntent(array $fields, string $idempotencyKey): array
    {
        $intent = $this->request('POST', '/v1/payment_intents', $fields, $idempotencyKey);
        $status = (string) ($intent['status'] ?? '');
        if ($status !== 'succeeded') {
            throw new HttpException(402, 'Platba ve Stripe neprošla.');
        }
        return [
            'id' => (string) ($intent['id'] ?? ''),
            'status' => $status,
        ];
    }

    private function createApplePayToken(string $paymentDataJson, string $idempotencyKey): string
    {
        $decoded = json_decode($paymentDataJson, true);
        if (!is_array($decoded)) {
            throw new HttpException(422, 'Apple Pay token je neplatný.');
        }
        $token = $this->request('POST', '/v1/tokens', [
            'pk_token' => $paymentDataJson,
        ], $idempotencyKey);
        $id = (string) ($token['id'] ?? '');
        if ($id === '') {
            throw new HttpException(402, 'Stripe token se nepodařilo vytvořit.');
        }
        return $id;
    }

    /** @param array<string, mixed> $fields */
    private function request(string $method, string $path, array $fields, string $idempotencyKey): array
    {
        $ch = curl_init('https://api.stripe.com' . $path);
        if ($ch === false) {
            throw new HttpException(502, 'Stripe je teď nedostupný.');
        }
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Idempotency-Key: ' . $idempotencyKey,
            'Stripe-Version: 2024-06-20',
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (!is_string($raw) || $raw === '') {
            throw new HttpException(502, 'Stripe neodpověděl.');
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            throw new HttpException(502, 'Stripe poslal neplatnou odpověď.');
        }
        if ($status >= 400) {
            $message = (string) (($json['error']['message'] ?? null) ?: 'Platba ve Stripe selhala.');
            throw new HttpException($status >= 500 ? 502 : 402, $message);
        }
        return $json;
    }
}
