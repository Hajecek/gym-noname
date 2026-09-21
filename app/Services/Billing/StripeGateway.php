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
     * @param array<string, string> $metadata
     * @return array{id:string,url:string,status:string}
     */
    public function createCheckoutSession(
        int $amountMinor,
        string $currency,
        string $description,
        string $successUrl,
        string $cancelUrl,
        string $idempotencyKey,
        array $metadata = [],
        ?int $expiresAt = null,
    ): array {
        $this->assertAmount($amountMinor);
        $fields = [
            'mode' => 'payment',
            'locale' => 'cs',
            'submit_type' => 'pay',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $metadata['payment'] ?? $idempotencyKey,
            'managed_payments[enabled]' => 'false',
            'line_items[0][quantity]' => '1',
            'line_items[0][price_data][currency]' => strtolower($currency),
            'line_items[0][price_data][unit_amount]' => (string) $amountMinor,
            'line_items[0][price_data][product_data][name]' => $description,
        ] + $this->metadataFields($metadata);
        if ($expiresAt !== null) {
            $fields['expires_at'] = (string) $expiresAt;
        }
        $session = $this->request('POST', '/v1/checkout/sessions', $fields, $idempotencyKey . ':cs');
        $url = (string) ($session['url'] ?? '');
        $id = (string) ($session['id'] ?? '');
        if ($url === '' || $id === '') {
            throw new HttpException(502, 'Stripe Checkout se nepodařilo otevřít.');
        }
        return [
            'id' => $id,
            'url' => $url,
            'status' => (string) ($session['status'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    public function retrieveCheckoutSession(string $sessionId): array
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '' || !str_starts_with($sessionId, 'cs_')) {
            throw new HttpException(422, 'Neplatná platební relace.');
        }
        return $this->request('GET', '/v1/checkout/sessions/' . rawurlencode($sessionId), [], $sessionId . ':get');
    }

    public function refundPayment(string $reference, string $idempotencyKey): void
    {
        $reference = trim($reference);
        $intent = $reference;
        if (str_starts_with($reference, 'cs_')) {
            $session = $this->retrieveCheckoutSession($reference);
            $intent = (string) ($session['payment_intent'] ?? '');
        }
        if (!str_starts_with($intent, 'pi_')) {
            throw new HttpException(422, 'Platbu teď nejde vrátit.');
        }
        $this->request('POST', '/v1/refunds', [
            'payment_intent' => $intent,
        ], $idempotencyKey);
    }

    /** @return array<string, mixed> */
    public function parseWebhook(string $payload, string $signatureHeader, string $secret): array
    {
        if ($secret === '' || !str_starts_with($secret, 'whsec_')) {
            throw new HttpException(503, 'Stripe webhook není nakonfigurovaný.');
        }
        $parts = [];
        foreach (explode(',', $signatureHeader) as $item) {
            [$key, $value] = array_pad(explode('=', trim($item), 2), 2, '');
            $parts[$key][] = $value;
        }
        $timestamp = (string) (($parts['t'][0] ?? ''));
        $signatures = $parts['v1'] ?? [];
        if ($timestamp === '' || $signatures === []) {
            throw new HttpException(400, 'Neplatný podpis Stripe.');
        }
        if (abs(time() - (int) $timestamp) > 300) {
            throw new HttpException(400, 'Podpis Stripe vypršel.');
        }
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        $ok = false;
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                $ok = true;
                break;
            }
        }
        if (!$ok) {
            throw new HttpException(400, 'Neplatný podpis Stripe.');
        }
        $event = json_decode($payload, true);
        if (!is_array($event)) {
            throw new HttpException(400, 'Neplatné tělo webhooku.');
        }
        return $event;
    }

    /**
     * @return array{id:string,status:string}
     */
    /**
     * @param array<string, string> $metadata
     * @return array{id:string,status:string}
     */
    public function chargeApplePay(string $paymentDataJson, int $amountMinor, string $currency, string $idempotencyKey, string $description, array $metadata = []): array
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
        ] + $this->metadataFields($metadata), $idempotencyKey . ':pi');
    }

    /**
     * Simulátor iOS nedoručí skutečný Apple Pay JSON. V APP_ENV=local + sk_test
     * se účtuje Stripe test karta, ne falešný úspěch bez Stripe.
     *
     * @return array{id:string,status:string}
     */
    /**
     * @param array<string, string> $metadata
     * @return array{id:string,status:string}
     */
    public function chargeTestCard(int $amountMinor, string $currency, string $idempotencyKey, string $description, array $metadata = []): array
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
        ] + $this->metadataFields($metadata), $idempotencyKey . ':test-pi');
    }

    /** @param array<string, string> $metadata @return array<string, string> */
    private function metadataFields(array $metadata): array
    {
        $fields = [];
        foreach ($metadata as $key => $value) {
            if ($value === '') {
                continue;
            }
            $fields['metadata[' . $key . ']'] = $value;
        }
        return $fields;
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
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ];
        if ($method !== 'GET') {
            $opts[CURLOPT_POSTFIELDS] = http_build_query($fields);
        }
        curl_setopt_array($ch, $opts);
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
