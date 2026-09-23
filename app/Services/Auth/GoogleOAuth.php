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
        $idToken = (string) ($token['id_token'] ?? '');
        if ($idToken === '') {
            throw new HttpException(502, 'Google nevrátil ověření účtu.');
        }
        $info = self::request('GET', 'https://oauth2.googleapis.com/tokeninfo?id_token=' . rawurlencode($idToken));
        $aud = (string) ($info['aud'] ?? '');
        $iss = (string) ($info['iss'] ?? '');
        $email = mb_strtolower(trim((string) ($info['email'] ?? '')));
        $verified = filter_var($info['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $sub = trim((string) ($info['sub'] ?? ''));
        if (
            $aud !== self::clientId()
            || !in_array($iss, ['accounts.google.com', 'https://accounts.google.com'], true)
            || $sub === ''
            || $email === ''
            || !$verified
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
