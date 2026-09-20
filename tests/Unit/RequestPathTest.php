<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestPathTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $server = [];

    protected function setUp(): void
    {
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    public function testApiPhpFileDoesNotStripApiPrefix(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['SCRIPT_NAME'] = '/api/v1/reservations.php';
        $_SERVER['REQUEST_URI'] = '/api/v1/reservations/8f3c2a10-9b47-4e6d-a1c2-0d5e7f8a9b21/cancel';
        $request = new Request();
        $this->assertSame('/api/v1/reservations/8f3c2a10-9b47-4e6d-a1c2-0d5e7f8a9b21/cancel', $request->path());
    }

    public function testFrontControllerKeepsApiPath(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/api/v1/reservations';
        $request = new Request();
        $this->assertSame('/api/v1/reservations', $request->path());
    }
}
