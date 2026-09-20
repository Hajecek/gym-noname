<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AvatarService;
use PHPUnit\Framework\TestCase;

final class AvatarServiceTest extends TestCase
{
    public function testUploadsHtaccessDoesNotUseBarePhpFlag(): void
    {
        $htaccess = (string) file_get_contents(dirname(__DIR__, 2) . '/uploads/.htaccess');
        $this->assertStringContainsString('<IfModule mod_php.c>', $htaccess);
        $this->assertDoesNotMatchRegularExpression('/^php_flag /m', $htaccess);
    }

    public function testResolveFileRejectsPathTraversal(): void
    {
        $this->assertNull(AvatarService::resolveFile('../.env'));
        $this->assertNull(AvatarService::resolveFile('not-an-image.txt'));
    }

    public function testMimeForKnownExtensions(): void
    {
        $this->assertSame('image/webp', AvatarService::mimeFor('avatar.webp'));
        $this->assertSame('image/png', AvatarService::mimeFor('avatar.png'));
        $this->assertSame('image/jpeg', AvatarService::mimeFor('avatar.jpg'));
    }

    public function testWebpUploadIsRejectedWhenGdLacksWebp(): void
    {
        if (AvatarService::supportsWebp()) {
            $this->markTestSkipped('GD má podporu WebP.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'pfwebp');
        $this->assertNotFalse($tmp);
        // Minimální platný 1×1 WebP, aby finfo vrátil image/webp.
        $bytes = hex2bin(
            '524946461a000000574542505650384c0d0000002f000000100101004c000000'
        );
        $this->assertNotFalse($bytes);
        file_put_contents($tmp, $bytes);

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if ($mime !== 'image/webp') {
            @unlink($tmp);
            $this->markTestSkipped('finfo nerozpoznal testovací WebP blob.');
        }

        $service = new AvatarService(new \App\Core\Database());
        try {
            $service->storeFromUpload(
                ['id' => 1, 'public_id' => '11111111-1111-4111-8111-111111111111'],
                ['error' => UPLOAD_ERR_OK, 'size' => filesize($tmp), 'tmp_name' => $tmp]
            );
            $this->fail('WebP bez podpory GD musí selhat.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('WebP', $e->getMessage());
        } finally {
            @unlink($tmp);
        }
    }
}
