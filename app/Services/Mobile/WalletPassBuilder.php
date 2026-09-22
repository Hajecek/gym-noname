<?php

declare(strict_types=1);

namespace App\Services\Mobile;

// Collectible Wallet pass. Apple stores it only after it is signed with a Pass Type ID certificate.
final class WalletPassBuilder
{
    public function __construct(
        private readonly string $passTypeIdentifier = 'pass.cz.privofit.cz',
        private readonly string $teamIdentifier = 'W6R3HZD7R4',
        private readonly ?string $certificate = null,
        private readonly ?string $privateKey = null,
        private readonly ?string $wwdr = null,
    ) {
    }

    public static function fromEnvironment(): self
    {
        $value = static function (string $key, string $default = ''): string {
            if (!function_exists('env_value')) {
                return $default;
            }
            $raw = env_value($key, $default);
            return is_string($raw) ? $raw : $default;
        };
        return new self(
            $value('WALLET_PASS_TYPE_ID', 'pass.cz.privofit.cz') ?: 'pass.cz.privofit.cz',
            $value('WALLET_TEAM_ID', 'W6R3HZD7R4') ?: 'W6R3HZD7R4',
            self::resolvePath($value('WALLET_PASS_CERT')),
            self::resolvePath($value('WALLET_PASS_KEY')),
            self::resolvePath($value('WALLET_PASS_WWDR')),
        );
    }

    private static function resolvePath(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        $root = dirname(__DIR__, 3);
        $candidates = [$value];
        if (!str_starts_with($value, DIRECTORY_SEPARATOR)) {
            $candidates[] = $root . '/' . ltrim($value, '/');
        }
        $candidates[] = $root . '/storage/wallet/' . basename($value);
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $user @param array<string, mixed> $membership */
    public function build(array $user, array $membership): string
    {
        $name = trim((string) ($user['first_name'] ?? ''));
        if ($name === '') {
            $name = 'PRIVOFIT';
        }
        $serial = trim((string) ($user['public_id'] ?? ''));
        if ($serial === '') {
            $serial = 'member';
        }
        $until = (string) ($membership['validUntil'] ?? gmdate('Y-m-d\TH:i:s\Z'));
        $auxiliary = [[
            'key' => 'until',
            'label' => 'Platí do',
            'value' => $until,
            'dateStyle' => 'PKDateStyleMedium',
        ]];
        if (isset($membership['remainingEntries']) && $membership['remainingEntries'] !== null) {
            $auxiliary[] = [
                'key' => 'entries',
                'label' => 'Zbývající vstupy',
                'value' => (string) $membership['remainingEntries'],
            ];
        }
        $back = [[
            'key' => 'status',
            'label' => 'Stav',
            'value' => $this->statusLabel((string) ($membership['status'] ?? 'inactive')),
        ]];
        if (!empty($user['username'])) {
            $back[] = ['key' => 'username', 'label' => 'Uživatelské jméno', 'value' => (string) $user['username']];
        }
        $pass = [
            'formatVersion' => 1,
            'passTypeIdentifier' => $this->passTypeIdentifier,
            'serialNumber' => $serial,
            'teamIdentifier' => $this->teamIdentifier,
            'organizationName' => 'PRIVOFIT',
            'description' => 'Sběratelská členská karta',
            'logoText' => 'PRIVOFIT',
            'foregroundColor' => 'rgb(232, 240, 228)',
            'backgroundColor' => 'rgb(16, 23, 20)',
            'labelColor' => 'rgb(198, 242, 26)',
            'expirationDate' => $until,
            'sharingProhibited' => true,
            'voided' => empty($membership['isActive']),
            'storeCard' => [
                'primaryFields' => [[
                    'key' => 'name',
                    'label' => 'DIGITÁLNÍ ČLENSKÁ KARTA',
                    'value' => $name,
                ]],
                'secondaryFields' => [[
                    'key' => 'title',
                    'label' => 'PRIVOFIT',
                    'value' => (string) ($membership['title'] ?? 'Členství'),
                ]],
                'auxiliaryFields' => $auxiliary,
                'backFields' => $back,
            ],
        ];
        $files = [
            'pass.json' => (string) json_encode($pass, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'icon.png' => $this->icon(29),
            'icon@2x.png' => $this->icon(58),
            'icon@3x.png' => $this->icon(87),
            'strip.png' => $this->strip(375, 144),
            'strip@2x.png' => $this->strip(750, 288),
            'strip@3x.png' => $this->strip(1125, 432),
        ];
        $manifest = [];
        foreach ($files as $filename => $contents) {
            $manifest[$filename] = sha1($contents);
        }
        $manifestJson = (string) json_encode($manifest, JSON_UNESCAPED_SLASHES);
        $files['manifest.json'] = $manifestJson;
        $signature = $this->signature($manifestJson);
        if ($signature !== null) {
            $files['signature'] = $signature;
        }
        return $this->zip($files);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Aktivní',
            'ending' => 'Brzy končí',
            'paused' => 'Pozastavené',
            default => 'Neaktivní',
        };
    }

    private function signature(string $manifest): ?string
    {
        if ($this->certificate === null || $this->privateKey === null || $this->wwdr === null) {
            return null;
        }
        if (!is_file($this->certificate) || !is_file($this->privateKey) || !is_file($this->wwdr)) {
            return null;
        }
        $directory = sys_get_temp_dir() . '/privofit-pass-' . bin2hex(random_bytes(6));
        if (!mkdir($directory, 0700, true) && !is_dir($directory)) {
            return null;
        }
        $manifestPath = $directory . '/manifest.json';
        $signaturePath = $directory . '/signature';
        file_put_contents($manifestPath, $manifest);
        $ok = openssl_pkcs7_sign(
            $manifestPath,
            $signaturePath,
            'file://' . $this->certificate,
            ['file://' . $this->privateKey, ''],
            [],
            PKCS7_BINARY | PKCS7_DETACHED,
            $this->wwdr
        );
        $der = null;
        if ($ok && is_file($signaturePath)) {
            $der = $this->derSignature((string) file_get_contents($signaturePath));
        }
        @unlink($manifestPath);
        @unlink($signaturePath);
        @rmdir($directory);
        return $der;
    }

    private function derSignature(string $message): ?string
    {
        if (!preg_match('/filename="smime\.p7s"\s*(.*?)\s*------/s', $message, $match)) {
            return null;
        }
        $decoded = base64_decode(preg_replace('/\s+/', '', $match[1]) ?? '', true);

        return is_string($decoded) && $decoded !== '' ? $decoded : null;
    }

    private function icon(int $pixels): string
    {
        return $this->png($pixels, $pixels, static function ($image, int $width, int $height): void {
            $lime = imagecolorallocate($image, 198, 242, 26);
            imagefilledrectangle($image, 0, 0, $width, $height, $lime);
            $ink = imagecolorallocate($image, 16, 23, 20);
            imagefilledrectangle($image, (int) ($width * 0.22), (int) ($height * 0.2), (int) ($width * 0.4), (int) ($height * 0.8), $ink);
            imagefilledellipse($image, (int) ($width * 0.58), (int) ($height * 0.38), (int) ($width * 0.42), (int) ($height * 0.34), $ink);
            imagefilledellipse($image, (int) ($width * 0.58), (int) ($height * 0.38), (int) ($width * 0.18), (int) ($height * 0.14), $lime);
        });
    }

    private function strip(int $width, int $height): string
    {
        return $this->png($width, $height, static function ($image, int $width, int $height): void {
            for ($y = 0; $y < $height; $y++) {
                $t = $height <= 1 ? 0 : $y / ($height - 1);
                $color = imagecolorallocate(
                    $image,
                    (int) (46 + (16 - 46) * $t),
                    (int) (63 + (23 - 63) * $t),
                    (int) (50 + (20 - 50) * $t)
                );
                imageline($image, 0, $y, $width, $y, $color);
            }
            $lime = imagecolorallocate($image, 198, 242, 26);
            imagesetthickness($image, max(4, (int) ($width * 0.012)));
            imagearc($image, (int) ($width * 0.92), (int) (-$height * 0.1), (int) ($height * 1.3), (int) ($height * 1.3), 0, 360, $lime);
        });
    }

    private function png(int $width, int $height, callable $draw): string
    {
        $image = imagecreatetruecolor($width, $height);
        if ($image === false) {
            return '';
        }
        imagealphablending($image, true);
        $draw($image, $width, $height);
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);
        return is_string($bytes) ? $bytes : '';
    }

    /** @param array<string, string> $files */
    private function zip(array $files): string
    {
        ksort($files);
        $local = '';
        $central = '';
        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $offset = strlen($local);
            $nameBytes = $name;
            $header = pack('VvvvvvVVVvv', 0x04034B50, 20, 0, 0, 0, 0, $crc, strlen($content), strlen($content), strlen($nameBytes), 0);
            $local .= $header . $nameBytes . $content;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0, 0, 0, 0, $crc, strlen($content), strlen($content), strlen($nameBytes), 0, 0, 0, 0, 0, $offset) . $nameBytes;
        }
        $centralOffset = strlen($local);
        $end = pack('VvvvvVVv', 0x06054B50, 0, 0, count($files), count($files), strlen($central), $centralOffset, 0);
        return $local . $central . $end;
    }
}
