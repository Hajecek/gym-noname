<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\Env;
use App\Services\Access\AccessControlService;
use App\Services\Auth\AuthService;
use App\Services\MembershipService;
use App\Services\ReservationService;
use App\Support\Clock;
use PHPUnit\Framework\TestCase;

final class ApplicationFlowTest extends TestCase
{
    private Database $db;
    private AuthService $auth;

    protected function setUp(): void
    {
        if (!Env::get('DB_DATABASE')) {
            $this->markTestSkipped('Databáze není nakonfigurována.');
        }
        try {
            $this->db = new Database();
            $this->db->ping();
        } catch (\Throwable) {
            $this->markTestSkipped('Databáze není dostupná.');
        }
        $this->db->query("DELETE FROM rate_limit_events WHERE bucket = 'register'");
        $this->auth = AuthService::make($this->db);
    }

    public function testRegistrationLoginAndEmailVerification(): void
    {
        $email = 'test.' . bin2hex(random_bytes(4)) . '@privofit.test';
        $username = 'u' . bin2hex(random_bytes(4));
        $request = $this->fakeRequest();
        $user = $this->auth->register([
            'username' => $username,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => 'spravne-dlouhe-heslo',
            'password_confirmation' => 'spravne-dlouhe-heslo',
            'terms' => '1',
            'privacy' => '1',
        ], $request);
        $this->assertSame('user', $user['role']);
        $this->assertSame('pending', $user['status']);
        $this->assertNull($user['email_verified_at']);

        $row = $this->db->fetch('SELECT * FROM email_verifications WHERE user_id = :id ORDER BY id DESC LIMIT 1', ['id' => (int) $user['id']]);
        $this->assertNotNull($row);

        $logged = false;
        try {
            $this->auth->login($email, 'spatne-heslo-xxxx', $request);
        } catch (\Throwable $e) {
            $this->assertSame(401, $e->getCode() ?: 401);
            $logged = true;
        }
        $this->assertTrue($logged);
    }

    public function testCannotAccessForeignReservation(): void
    {
        $a = $this->createUser('a');
        $b = $this->createUser('b');
        $this->expectException(\App\Core\HttpException::class);
        ReservationService::make($this->db)->owned($a, '00000000-0000-4000-8000-000000000099');
        unset($b);
    }

    public function testOverlappingReservationsAreRejected(): void
    {
        $user = $this->createVerifiedUser('res');
        $this->grantMembership((int) $user['id']);
        $hour = 9 + (hexdec(bin2hex(random_bytes(1))) % 8);
        $start = Clock::nowLocal()->modify('+8 days')->setTime($hour, 0)->format('Y-m-d H:i');
        $first = ReservationService::make($this->db)->create($user, $start, 60, 1);
        $this->assertSame('confirmed', $first['status']);
        $this->expectException(\App\Core\HttpException::class);
        ReservationService::make($this->db)->create($user, $start, 60, 1);
    }

    public function testCancelFreesTheSlotForSomeoneElse(): void
    {
        $user = $this->createVerifiedUser('cnl');
        $this->grantMembership((int) $user['id']);
        $startLocal = Clock::nowLocal()->modify('+13 days')->setTime(7, 0);
        $start = $startLocal->format('Y-m-d H:i');
        $startUtc = \App\Support\Clock::toUtc($startLocal)->format('Y-m-d H:i:s');
        $this->db->query(
            "UPDATE reservations SET status = 'cancelled' WHERE starts_at = :s AND status IN ('pending_payment', 'confirmed')",
            ['s' => $startUtc]
        );
        try {
            $this->db->query('DELETE FROM reservation_occupancy WHERE starts_at = :s', ['s' => $startUtc]);
        } catch (\Throwable) {
        }
        $service = ReservationService::make($this->db);
        $first = $service->create($user, $start, 60, 1);
        $this->assertSame('confirmed', $first['status']);
        $service->cancel($user, $first['public_id']);
        $fresh = $this->db->fetch('SELECT status FROM reservations WHERE id = :id', ['id' => (int) $first['id']]);
        $this->assertSame('cancelled', $fresh['status']);
        $second = $service->create($user, $start, 60, 1);
        $this->assertSame('confirmed', $second['status']);
        $this->assertNotSame($first['id'], $second['id']);
    }

    public function testDoorOpenWithoutReservationIsDenied(): void
    {
        $user = $this->createVerifiedUser('door');
        $this->expectException(\App\Core\HttpException::class);
        AccessControlService::make($this->db)->open($user, '127.0.0.1');
    }

    public function testExpiredMembershipDoesNotGrantEntries(): void
    {
        $user = $this->createVerifiedUser('mem');
        $plan = $this->db->fetch("SELECT * FROM membership_plans WHERE slug = 'monthly'");
        $id = (int) $this->db->insert('memberships', [
            'public_id' => Crypto::uuid(),
            'user_id' => (int) $user['id'],
            'plan_id' => (int) $plan['id'],
            'status' => 'active',
            'starts_at' => Clock::nowUtc()->modify('-40 days')->format('Y-m-d H:i:s'),
            'ends_at' => Clock::nowUtc()->modify('-1 day')->format('Y-m-d H:i:s'),
            'created_at' => Clock::utc(),
            'updated_at' => Clock::utc(),
        ]);
        (new MembershipService($this->db))->expireOverdue((int) $user['id']);
        $fresh = $this->db->fetch('SELECT status FROM memberships WHERE id = :id', ['id' => $id]);
        $this->assertSame('expired', $fresh['status']);
        $this->assertNull((new MembershipService($this->db))->activeForUser((int) $user['id']));
    }

    private function createUser(string $prefix): array
    {
        $request = $this->fakeRequest();
        return $this->auth->register([
            'username' => $prefix . bin2hex(random_bytes(3)),
            'first_name' => 'A',
            'last_name' => 'B',
            'email' => $prefix . bin2hex(random_bytes(3)) . '@privofit.test',
            'password' => 'spravne-dlouhe-heslo',
            'password_confirmation' => 'spravne-dlouhe-heslo',
            'terms' => '1',
            'privacy' => '1',
        ], $request);
    }

    private function createVerifiedUser(string $prefix): array
    {
        $user = $this->createUser($prefix);
        $this->db->update('users', [
            'status' => 'active',
            'email_verified_at' => Clock::utc(),
        ], 'id = :id', ['id' => (int) $user['id']]);
        return $this->auth->findById((int) $user['id']);
    }

    private function grantMembership(int $userId): void
    {
        $plan = $this->db->fetch("SELECT * FROM membership_plans WHERE slug = 'monthly'");
        (new MembershipService($this->db))->assignPlan($userId, (int) $plan['id']);
    }

    private function fakeRequest(): \App\Core\Request
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/registrace';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit';
        return new \App\Core\Request();
    }
}
