<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Mobile\WalletPassBuilder;
use PHPUnit\Framework\TestCase;

final class WalletPassBuilderTest extends TestCase
{
    public function testCollectiblePassContainsTheMember(): void
    {
        $bytes = (new WalletPassBuilder())->build(
            ['public_id' => 'member-1', 'first_name' => 'Alex', 'username' => 'alex_demo'],
            [
                'title' => 'Tvůj prostor',
                'validUntil' => '2026-10-22T00:00:00Z',
                'remainingEntries' => 8,
                'isActive' => true,
                'validFrom' => '2026-09-01T00:00:00Z',
                'status' => 'active',
            ]
        );

        $this->assertStringStartsWith('PK', $bytes);
        $this->assertStringContainsString('Alex', $bytes);
        $this->assertStringContainsString('pass.cz.privofit.cz', $bytes);
        $this->assertStringContainsString('Sběratelská členská karta', $bytes);

        $path = tempnam(sys_get_temp_dir(), 'pass');
        $this->assertNotFalse($path);
        file_put_contents($path, $bytes);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $json = $zip->getFromName('pass.json');
        $zip->close();
        @unlink($path);
        $this->assertIsString($json);
        $this->assertStringContainsString('"value":"Alex"', (string) $json);
    }

    public function testInstalledCertificateSignsThePass(): void
    {
        $root = dirname(__DIR__, 2);
        $certificate = $root . '/storage/wallet/pass.pem';
        $key = $root . '/storage/wallet/pass.key';
        $wwdr = $root . '/storage/wallet/wwdr.pem';
        if (!is_file($certificate) || !is_file($key) || !is_file($wwdr)) {
            $this->markTestSkipped('Wallet certificates are not installed.');
        }

        $bytes = (new WalletPassBuilder(
            certificate: $certificate,
            privateKey: $key,
            wwdr: $wwdr,
        ))->build(
            ['public_id' => 'member-1', 'first_name' => 'Alex', 'username' => 'alex_demo'],
            [
                'title' => 'Tvůj prostor',
                'validUntil' => '2026-10-22T00:00:00Z',
                'remainingEntries' => 8,
                'isActive' => true,
                'status' => 'active',
            ]
        );

        $path = tempnam(sys_get_temp_dir(), 'pass');
        $this->assertNotFalse($path);
        file_put_contents($path, $bytes);
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $signature = $zip->getFromName('signature');
        $manifest = $zip->getFromName('manifest.json');
        $zip->close();
        @unlink($path);
        $this->assertIsString($signature);
        $this->assertGreaterThan(1000, strlen($signature));
        $this->assertIsString($manifest);
        $this->assertStringStartsWith('{', $manifest);
    }
}
