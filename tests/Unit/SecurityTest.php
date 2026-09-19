<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Crypto;
use App\Core\Validator;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase
{
    public function testUuidIsVersion4(): void
    {
        $uuid = Crypto::uuid();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function testPasswordHashUsesModernAlgorithm(): void
    {
        $hash = Crypto::hashPassword('dlouhe-testovaci-heslo-123');
        $this->assertNotSame('dlouhe-testovaci-heslo-123', $hash);
        $this->assertTrue(Crypto::verifyPassword('dlouhe-testovaci-heslo-123', $hash));
        $this->assertFalse(Crypto::verifyPassword('jine-heslo', $hash));
        $this->assertTrue(str_contains($hash, 'argon2id') || str_starts_with($hash, '$2y$'));
    }

    public function testSignedPayloadCannotBeTampered(): void
    {
        putenv('APP_KEY=test-key-for-unit-tests-not-for-production');
        \App\Core\Env::set('APP_KEY', 'test-key-for-unit-tests-not-for-production');
        $token = Crypto::signPayload(['sub' => 'abc', 'exp' => time() + 60, 'typ' => 'access']);
        $this->assertNotNull(Crypto::verifyPayload($token));
        $this->assertNull(Crypto::verifyPayload($token . 'x'));
    }

    public function testUsernameRules(): void
    {
        $ok = new Validator();
        $ok->username('username', 'michal.01');
        $this->assertFalse($ok->fails());

        $bad = new Validator();
        $bad->username('username', 'Áno');
        $this->assertTrue($bad->fails());
    }

    public function testCsrfRejectsInvalidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        \App\Core\Session::start();
        \App\Core\Session::set('_csrf', 'expected-token');
        $this->assertFalse(\App\Core\Csrf::verify('other-token'));
        $this->assertTrue(\App\Core\Csrf::verify('expected-token'));
    }
}
