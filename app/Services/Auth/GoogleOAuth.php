<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\HttpException;

final class GoogleOAuth
{
    public static function configured(): bool
    {
        return self::clientId() !== '' && self::clientSecret() !== '';
    }

    public static function clientId(): string
    {
        return trim((string) env_value('GOOGLE_CLIENT_ID', ''));
    }

    public static function authorizationUrl(string $state, string $redirectUri): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => self::clientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);
    }

    /** @return array{sub:string,email:string,given_name:string,family_name:string,name:string} */
    public static function profile(string $code, string $redirectUri): array
    {
        $token = self::request('POST', 'https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);
        return self::identity((string) ($token['id_token'] ?? ''), self::clientId(), null);
    }

    public static function mobileConfigured(): bool
    {
        return self::mobileClientId() !== '' && self::mobileRedirectUri() !== '';
    }

    public static function mobileClientId(): string
    {
        return trim((string) env_value('GOOGLE_IOS_CLIENT_ID', ''));
    }

    /**
     * iOS posílá authorization code z veřejného klienta. Secret zůstává na serveru,
     * u iOS klienta se nepoužívá. Ověří se PKCE, audience, issuer, e-mail a nonce.
     *
     * @return array{sub:string,email:string,given_name:string,family_name:string,name:string}
     */
    public static function profileFromMobile(string $code, string $clientId, string $codeVerifier, string $redirectUri, string $nonce): array
    {
        if (!self::mobileConfigured()) {
            throw new HttpException(503, 'Přihlášení přes Google v aplikaci ještě není nastavené.');
        }
        $clientId = trim($clientId);
        $redirectUri = trim($redirectUri);
        $nonce = trim($nonce);
        if (
            !hash_equals(self::mobileClientId(), $clientId)
            || !hash_equals(self::mobileRedirectUri(), $redirectUri)
            || !preg_match('/^[A-Za-z0-9\-._~]{43,128}$/', $codeVerifier)
            || !preg_match('/^[A-Za-z0-9\-._~\/+=]{8,512}$/', $code)
            || strlen($nonce) < 16
            || strlen($nonce) > 256
        ) {
            throw new HttpException(403, 'Google účet se nepodařilo ověřit.');
        }
        $token = self::request('POST', 'https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => self::mobileClientId(),
            'redirect_uri' => self::mobileRedirectUri(),
            'grant_type' => 'authorization_code',
            'code_verifier' => $codeVerifier,
        ]);

        return self::identity((string) ($token['id_token'] ?? ''), self::mobileClientId(), $nonce);
    }

    /** @return array{sub:string,email:string,given_name:string,family_name:string,name:string} */
    private static function identity(string $idToken, string $audience, ?string $nonce): array
    {
        if ($idToken === '' || strlen($idToken) > 8192) {
            throw new HttpException(502, 'Google nevrátil ověření účtu.');
        }
        $info = self::request('GET', 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken));
        $aud = (string) ($info['aud'] ?? '');
        $iss = (string) ($info['iss'] ?? '');
        $email = mb_strtolower(trim((string) ($info['email'] ?? '')));
        $verified = filter_var($info['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $sub = trim((string) ($info['sub'] ?? ''));
        $nonceOk = $nonce === null || hash_equals($nonce, (string) ($info['nonce'] ?? ''));
        if (
            $aud !== $audience
            || !in_array($iss, ['accounts.google.com', 'https://accounts.google.com'], true)
            || $sub === ''
            || $email === ''
            || !$verified
            || !$nonceOk
        ) {
            throw new HttpException(403, 'Google účet se nepodařilo ověřit.');
        }

        return [
            'sub' => $sub,
            'email' => $email,
            'given_name' => trim((string) ($info['given_name'] ?? '')),
            'family_name' => trim((string) ($info['family_name'] ?? '')),
            'name' => trim((string) ($info['name'] ?? '')),
        ];
    }

    private static function mobileRedirectUri(): string
    {
        return trim((string) env_value('GOOGLE_IOS_REDIRECT_URI', ''));
    }

    private static function clientSecret(): string
    {
        return trim((string) env_value('GOOGLE_CLIENT_SECRET', ''));
    }

    /** @param array<string, string> $fields
     *  @return array<string, mixed>
     */
    private static function request(string $method, string $url, array $fields = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new HttpException(502, 'Google je teď nedostupný.');
        }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($fields);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($json) || $status >= 400) {
            throw new HttpException(502, 'Google přihlášení se nepovedlo. Zkus to znovu.');
        }
        return $json;
    }
}
