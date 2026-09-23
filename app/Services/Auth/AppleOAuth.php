<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Core\HttpException;

final class AppleOAuth
{
    private const COOKIE = 'privofit_apple';

    public static function configured(): bool
    {
        return self::clientId() !== ''
            && self::teamId() !== ''
            && self::keyId() !== ''
            && is_readable(self::keyPath());
    }

    public static function clientId(): string
    {
        return trim((string) env_value('APPLE_CLIENT_ID', ''));
    }

    /** @return array{state:string,nonce:string} */
    public static function issueState(): array
    {
        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));
        $exp = time() + 600;
        $sig = hash_hmac('sha256', $state . '|' . $nonce . '|' . $exp, self::appKey());
        setcookie(self::COOKIE, $state . '.' . $nonce . '.' . $exp . '.' . $sig, [
            'expires' => $exp,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None',
        ]);
        return ['state' => $state, 'nonce' => $nonce];
    }

    public static function consumeNonce(string $state): ?string
    {
        $raw = (string) ($_COOKIE[self::COOKIE] ?? '');
        setcookie(self::COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None',
        ]);
        $parts = explode('.', $raw);
        if (count($parts) !== 4) {
            return null;
        }
        [$savedState, $nonce, $exp, $sig] = $parts;
        $expected = hash_hmac('sha256', $savedState . '|' . $nonce . '|' . $exp, self::appKey());
        if (!hash_equals($expected, $sig) || !hash_equals($savedState, $state) || (int) $exp < time()) {
            return null;
        }
        return $nonce;
    }

    public static function authorizationUrl(string $state, string $nonce, string $redirectUri): string
    {
        return 'https://appleid.apple.com/auth/authorize?' . http_build_query([
            'client_id' => self::clientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code id_token',
            'response_mode' => 'form_post',
            'scope' => 'name email',
            'state' => $state,
            'nonce' => $nonce,
        ]);
    }

    /**
     * @return array{sub:string,email:string,given_name:string,family_name:string,name:string}
     */
    public static function profile(string $code, string $idToken, string $redirectUri, string $nonce, ?string $userJson): array
    {
        $claims = self::verifiedClaims($idToken, $nonce);
        $token = self::request('https://appleid.apple.com/auth/token', [
            'client_id' => self::clientId(),
            'client_secret' => self::clientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);
        $second = self::verifiedClaims((string) ($token['id_token'] ?? ''), $nonce, false);
        if (($second['sub'] ?? '') !== ($claims['sub'] ?? '')) {
            throw new HttpException(403, 'Apple účet se nepodařilo ověřit.');
        }
        $email = mb_strtolower(trim((string) ($claims['email'] ?? '')));
        $verified = filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($email === '' || !$verified) {
            throw new HttpException(403, 'Apple neposlal ověřený e-mail. Při souhlasu ho nech sdílet.');
        }
        $given = '';
        $family = '';
        if (is_string($userJson) && $userJson !== '') {
            $user = json_decode($userJson, true);
            if (is_array($user)) {
                $given = trim((string) ($user['name']['firstName'] ?? ''));
                $family = trim((string) ($user['name']['lastName'] ?? ''));
            }
        }

        return [
            'sub' => (string) $claims['sub'],
            'email' => $email,
            'given_name' => $given,
            'family_name' => $family,
            'name' => trim($given . ' ' . $family),
        ];
    }

    public static function bundleId(): string
    {
        $id = trim((string) env_value('APPLE_BUNDLE_ID', 'cz.privofit.app'));
        return $id !== '' ? $id : 'cz.privofit.app';
    }

    public static function mobileNonce(string $rawNonce): string
    {
        return hash('sha256', $rawNonce);
    }

    /**
     * Nativní Sign in with Apple. Audience tokenu je bundle ID aplikace.
     * Nonce v tokenu je SHA-256 hex surového nonce z aplikace.
     *
     * @return array{sub:string,email:string,given_name:string,family_name:string,name:string}
     */
    public static function profileFromMobile(
        string $identityToken,
        string $rawNonce,
        string $authorizationCode,
        ?string $givenName,
        ?string $familyName
    ): array {
        $rawNonce = trim($rawNonce);
        $authorizationCode = trim($authorizationCode);
        if (
            strlen($identityToken) < 20
            || strlen($identityToken) > 8192
            || strlen($rawNonce) < 16
            || strlen($rawNonce) > 256
            || $authorizationCode === ''
            || strlen($authorizationCode) > 512
        ) {
            throw new HttpException(403, 'Apple účet se nepodařilo ověřit.');
        }
        $nonce = self::mobileNonce($rawNonce);
        $claims = self::verifiedClaims($identityToken, $nonce, true, [self::bundleId()]);
        if (self::canSignClientSecret()) {
            $token = self::request('https://appleid.apple.com/auth/token', [
                'client_id' => self::bundleId(),
                'client_secret' => self::clientSecret(self::bundleId()),
                'code' => $authorizationCode,
                'grant_type' => 'authorization_code',
            ]);
            $second = self::verifiedClaims((string) ($token['id_token'] ?? ''), $nonce, false, [self::bundleId()]);
            if (($second['sub'] ?? '') !== ($claims['sub'] ?? '')) {
                throw new HttpException(403, 'Apple účet se nepodařilo ověřit.');
            }
        }
        $email = mb_strtolower(trim((string) ($claims['email'] ?? '')));
        $verified = filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($email === '' || !$verified) {
            throw new HttpException(403, 'Apple neposlal ověřený e-mail. Při souhlasu ho nech sdílet.');
        }
        $given = self::personName($givenName);
        $family = self::personName($familyName);

        return [
            'sub' => (string) $claims['sub'],
            'email' => $email,
            'given_name' => $given,
            'family_name' => $family,
            'name' => trim($given . ' ' . $family),
        ];
    }

    private static function personName(?string $value): string
    {
        $name = trim((string) $value);
        $name = preg_replace('/\s+/u', ' ', $name) ?? '';
        return mb_substr($name, 0, 100);
    }

    /** @param list<string>|null $audiences
     *  @return array<string, mixed>
     */
    private static function verifiedClaims(string $jwt, string $nonce, bool $requireNonce = true, ?array $audiences = null): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new HttpException(403, 'Apple účet se nepodařilo ověřit.');
        }
        $header = json_decode(self::b64urlDecode($parts[0]), true);
        $claims = json_decode(self::b64urlDecode($parts[1]), true);
        $signature = self::b64urlDecode($parts[2]);
        if (!is_array($header) || !is_array($claims) || $signature === '') {
            throw new HttpException(403, 'Apple účet se nepodařilo ověřit.');
        }
        $key = self::publicKey((string) ($header['kid'] ?? ''));
        $signed = $parts[0] . '.' . $parts[1];
        $alg = (string) ($header['alg'] ?? '');
        $ok = match ($alg) {
            'RS256' => openssl_verify($signed, $signature, $key, OPENSSL_ALGO_SHA256),
            'ES256' => strlen($signature) === 64
                ? openssl_verify($signed, self::rawToDer($signature), $key, OPENSSL_ALGO_SHA256)
                : 0,
            default => 0,
        };
        $allowed = array_values(array_filter(
            $audiences ?? [self::clientId()],
            static fn (mixed $item): bool => is_string($item) && $item !== ''
        ));
        $aud = $claims['aud'] ?? '';
        $audOk = is_string($aud)
            ? in_array($aud, $allowed, true)
            : (is_array($aud) && array_intersect($allowed, $aud) !== []);
        $nonceOk = hash_equals($nonce, (string) ($claims['nonce'] ?? ''));
        if (!$requireNonce && !isset($claims['nonce'])) {
            $nonceOk = true;
        }
        if (
            $ok !== 1
            || !in_array($alg, ['RS256', 'ES256'], true)
            || ($claims['iss'] ?? '') !== 'https://appleid.apple.com'
            || !$audOk
            || !$nonceOk
            || (int) ($claims['exp'] ?? 0) < time() - 60
            || trim((string) ($claims['sub'] ?? '')) === ''
        ) {
            throw new HttpException(403, 'Apple účet se nepodařilo ověřit.');
        }
        return $claims;
    }

    private static function publicKey(string $kid): string
    {
        $json = self::request('https://appleid.apple.com/auth/keys');
        foreach ($json['keys'] ?? [] as $key) {
            if (!is_array($key) || ($key['kid'] ?? '') !== $kid) {
                continue;
            }
            if (($key['kty'] ?? '') === 'RSA') {
                return self::rsaPem((string) ($key['n'] ?? ''), (string) ($key['e'] ?? ''));
            }
            if (($key['kty'] ?? '') === 'EC') {
                $x = self::b64urlDecode((string) ($key['x'] ?? ''));
                $y = self::b64urlDecode((string) ($key['y'] ?? ''));
                if (strlen($x) !== 32 || strlen($y) !== 32) {
                    continue;
                }
                $prefix = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');
                $spki = $prefix . "\x04" . $x . $y;
                return self::pem($spki);
            }
        }
        throw new HttpException(403, 'Apple účet se nepodařilo ověřit.');
    }

    private static function rsaPem(string $modulus, string $exponent): string
    {
        $mod = self::b64urlDecode($modulus);
        $exp = self::b64urlDecode($exponent);
        if ($mod !== '' && (ord($mod[0]) & 0x80) === 0x80) {
            $mod = "\x00" . $mod;
        }
        if ($exp !== '' && (ord($exp[0]) & 0x80) === 0x80) {
            $exp = "\x00" . $exp;
        }
        $rsa = self::asn1(0x30, self::asn1(0x02, $mod) . self::asn1(0x02, $exp));
        $algo = hex2bin('300d06092a864886f70d0101010500');
        return self::pem(self::asn1(0x30, $algo . self::asn1(0x03, "\x00" . $rsa)));
    }

    private static function asn1(int $tag, string $value): string
    {
        $len = strlen($value);
        if ($len < 128) {
            return chr($tag) . chr($len) . $value;
        }
        $bytes = ltrim(pack('N', $len), "\x00");
        return chr($tag) . chr(0x80 | strlen($bytes)) . $bytes . $value;
    }

    private static function pem(string $der): string
    {
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function canSignClientSecret(): bool
    {
        return self::teamId() !== '' && self::keyId() !== '' && is_readable(self::keyPath());
    }

    private static function clientSecret(?string $subject = null): string
    {
        $subject = $subject ?? self::clientId();
        if ($subject === '') {
            throw new HttpException(500, 'Přihlášení přes Apple ještě není nastavené.');
        }
        $header = self::b64url((string) json_encode(['alg' => 'ES256', 'kid' => self::keyId()], JSON_THROW_ON_ERROR));
        $now = time();
        $payload = self::b64url((string) json_encode([
            'iss' => self::teamId(),
            'iat' => $now,
            'exp' => $now + 3600,
            'aud' => 'https://appleid.apple.com',
            'sub' => $subject,
        ], JSON_THROW_ON_ERROR));
        $data = $header . '.' . $payload;
        $key = openssl_pkey_get_private((string) file_get_contents(self::keyPath()));
        if ($key === false) {
            throw new HttpException(500, 'Přihlášení přes Apple ještě není nastavené.');
        }
        $der = '';
        if (!openssl_sign($data, $der, $key, OPENSSL_ALGO_SHA256)) {
            throw new HttpException(500, 'Přihlášení přes Apple ještě není nastavené.');
        }
        return $data . '.' . self::b64url(self::derToRaw($der));
    }

    private static function teamId(): string
    {
        return trim((string) env_value('APPLE_TEAM_ID', ''));
    }

    private static function keyId(): string
    {
        return trim((string) env_value('APPLE_KEY_ID', ''));
    }

    private static function keyPath(): string
    {
        $configured = trim((string) env_value('APPLE_PRIVATE_KEY', ''));
        if ($configured === '') {
            return '';
        }
        if ($configured[0] !== '/') {
            $configured = dirname(__DIR__, 3) . '/' . ltrim($configured, '/');
        }
        return $configured;
    }

    private static function appKey(): string
    {
        $key = trim((string) env_value('APP_KEY', ''));
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }
        return $key;
    }

    /** @param array<string, string> $fields
     *  @return array<string, mixed>
     */
    private static function request(string $url, array $fields = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new HttpException(502, 'Apple je teď nedostupný.');
        }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ];
        if ($fields !== []) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($fields);
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $json = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($json) || $status >= 400) {
            throw new HttpException(502, 'Apple přihlášení se nepovedlo. Zkus to znovu.');
        }
        return $json;
    }

    private static function b64url(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function b64urlDecode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }
        $out = base64_decode($data, true);
        if (!is_string($out)) {
            throw new HttpException(403, 'Apple účet se nepodařilo ověřit.');
        }
        return $out;
    }

    private static function derToRaw(string $der): string
    {
        $pos = 0;
        if (ord($der[$pos++]) !== 0x30) {
            throw new HttpException(500, 'Přihlášení přes Apple ještě není nastavené.');
        }
        $len = ord($der[$pos++]);
        if ($len & 0x80) {
            $pos += $len & 0x7f;
        }
        $read = static function () use ($der, &$pos): string {
            if (ord($der[$pos++]) !== 0x02) {
                throw new HttpException(500, 'Přihlášení přes Apple ještě není nastavené.');
            }
            $size = ord($der[$pos++]);
            $int = substr($der, $pos, $size);
            $pos += $size;
            if ($int !== '' && ord($int[0]) === 0x00) {
                $int = substr($int, 1);
            }
            return str_pad(substr($int, -32), 32, "\x00", STR_PAD_LEFT);
        };
        return $read() . $read();
    }

    private static function rawToDer(string $raw): string
    {
        $int = static function (string $value): string {
            $value = ltrim($value, "\x00");
            if ($value === '') {
                $value = "\x00";
            }
            if ((ord($value[0]) & 0x80) === 0x80) {
                $value = "\x00" . $value;
            }
            return "\x02" . chr(strlen($value)) . $value;
        };
        $seq = $int(substr($raw, 0, 32)) . $int(substr($raw, 32, 32));
        return "\x30" . chr(strlen($seq)) . $seq;
    }
}
