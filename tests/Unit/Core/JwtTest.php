<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Exceptions\UnauthorizedException;
use App\Core\Jwt;
use PHPUnit\Framework\TestCase;

final class JwtTest extends TestCase
{
    // HS256 requires a key of at least 256 bits (32 bytes) — firebase/php-jwt rejects shorter ones.
    private const TEST_SECRET = 'test-secret-at-least-32-bytes-long!';

    public function testIssueAndDecodeRoundTrip(): void
    {
        $jwt = new Jwt(self::TEST_SECRET);
        $token = $jwt->issue(['sub' => 42, 'email' => 'user@example.com', 'type' => 'access'], 3600);

        $claims = $jwt->decode($token);

        self::assertSame(42, $claims['sub']);
        self::assertSame('user@example.com', $claims['email']);
        self::assertSame('access', $claims['type']);
        self::assertArrayHasKey('exp', $claims);
        self::assertArrayHasKey('jti', $claims);
    }

    public function testExpiredTokenThrows(): void
    {
        $jwt = new Jwt(self::TEST_SECRET);
        $token = $jwt->issue(['sub' => 1], -10);

        $this->expectException(UnauthorizedException::class);
        $jwt->decode($token);
    }

    public function testTamperedTokenThrows(): void
    {
        $jwt = new Jwt(self::TEST_SECRET);
        $token = $jwt->issue(['sub' => 1], 3600);

        $this->expectException(UnauthorizedException::class);
        (new Jwt('different-secret-also-at-least-32-bytes!'))->decode($token);
    }
}
