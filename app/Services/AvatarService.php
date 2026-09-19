<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\Logger;

final class AvatarService
{
    private const MAX_BYTES = 5242880;
    private const MAX_PIXELS = 40000000;
    private const SIZE = 512;
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(private readonly Database $db)
    {
    }

    public function storeFromUpload(array $user, array $file, array $crop = []): string
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Nahrání souboru se nezdařilo.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('Obrázek je větší než 5 MB.');
        }

        $tmp = (string) $file['tmp_name'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Povolené formáty jsou JPEG, PNG a WebP.');
        }

        $image = $this->decode($tmp, $mime);
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width * $height > self::MAX_PIXELS) {
            imagedestroy($image);
            throw new \RuntimeException('Obrázek má příliš mnoho pixelů.');
        }

        $square = $this->cropToSquare($image, $width, $height, $crop);
        imagedestroy($image);
        $resized = imagescale($square, self::SIZE, self::SIZE);
        imagedestroy($square);
        if ($resized === false) {
            throw new \RuntimeException('Zpracování obrázku selhalo.');
        }

        $filename = $user['public_id'] . '-' . Crypto::token(8) . '.webp';
        $storageDir = dirname(__DIR__, 2) . '/storage/uploads/avatars';
        $publicDir = dirname(__DIR__, 2) . '/uploads/avatars';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0750, true);
        }
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        $storagePath = $storageDir . '/' . $filename;
        if (function_exists('imagewebp')) {
            imagewebp($resized, $storagePath, 85);
        } else {
            $filename = $user['public_id'] . '-' . Crypto::token(8) . '.png';
            $storagePath = $storageDir . '/' . $filename;
            imagepng($resized, $storagePath, 7);
        }
        imagedestroy($resized);

        $publicPath = $publicDir . '/' . $filename;
        if (!copy($storagePath, $publicPath)) {
            throw new \RuntimeException('Uložení náhledu selhalo.');
        }

        $this->deleteFile($user['avatar_path'] ?? null);
        $this->db->update('users', ['avatar_path' => $filename], 'id = :id', ['id' => (int) $user['id']]);
        return $filename;
    }

    public function delete(?array $user): void
    {
        if (!$user) {
            return;
        }
        $this->deleteFile($user['avatar_path'] ?? null);
        $this->db->update('users', ['avatar_path' => null], 'id = :id', ['id' => (int) $user['id']]);
    }

    private function decode(string $path, string $mime): \GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default => false,
        };
        if (!$image instanceof \GdImage) {
            throw new \RuntimeException('Soubor se nepodařilo načíst jako obrázek.');
        }
        return $image;
    }

    private function cropToSquare(\GdImage $image, int $width, int $height, array $crop): \GdImage
    {
        if (isset($crop['x'], $crop['y'], $crop['size'])) {
            $size = max(1, (int) $crop['size']);
            $x = max(0, (int) $crop['x']);
            $y = max(0, (int) $crop['y']);
            $size = min($size, $width - $x, $height - $y);
        } else {
            $size = min($width, $height);
            $x = (int) (($width - $size) / 2);
            $y = (int) (($height - $size) / 2);
        }
        $cropped = imagecrop($image, ['x' => $x, 'y' => $y, 'width' => $size, 'height' => $size]);
        if (!$cropped instanceof \GdImage) {
            throw new \RuntimeException('Ořez obrázku selhal.');
        }
        return $cropped;
    }

    private function deleteFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }
        $base = basename($filename);
        foreach ([
            dirname(__DIR__, 2) . '/storage/uploads/avatars/' . $base,
            dirname(__DIR__, 2) . '/uploads/avatars/' . $base,
        ] as $path) {
            if (is_file($path) && !unlink($path)) {
                Logger::warning('Nepodařilo se smazat avatar', ['path' => basename($path)]);
            }
        }
    }
}
