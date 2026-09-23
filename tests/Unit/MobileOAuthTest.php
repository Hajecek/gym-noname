<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Env;
use App\Core\HttpException;
use App\Services\Auth\AppleOAuth;
use App\Services\Auth\GoogleOAuth;
use PHPUnit\Framework\TestCase;

final class MobileOAuthTest extends TestCase
{
    public function testAppleMobileNonceIsSha256Hex(): void
    {
        $raw = 'raw-nonce-from-the-app';
        $this->assertSame(hash('sha256', $raw), AppleOAuth::mobileNonce($raw));
    }

    public function testAppleMobileRejectsAShortTokenBeforeCallingApple(): void
    {
        $this->expectException(HttpException::class);
        AppleOAuth::profileFromMobile('short-token', 'raw-nonce-value-xxxx', 'auth-code', null, null);
    }

    public function testGoogleMobileRejectsAForeignClientBeforeCallingGoogle(): void
    {
        Env::set('GOOGLE_IOS_CLIENT_ID', 'ios-client.apps.googleusercontent.com');
        Env::set('GOOGLE_IOS_REDIRECT_URI', 'com.googleusercontent.apps.ios-client:/oauth2redirect');
        try {
            GoogleOAuth::profileFromMobile(
                '4/0AeanS0123456789',
                'other.apps.googleusercontent.com',
                str_repeat('a', 43),
                'com.googleusercontent.apps.ios-client:/oauth2redirect',
                'nonce-value-long-enough'
            );
            $this->fail('Cizí Google klient neměl projít.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->status);
        } finally {
            Env::set('GOOGLE_IOS_CLIENT_ID', '');
            Env::set('GOOGLE_IOS_REDIRECT_URI', '');
        }
    }
}
