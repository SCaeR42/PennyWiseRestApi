<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\UnauthorizedException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT as FirebaseJwt;
use Firebase\JWT\Key;

final class Jwt
{
    private const ALGO = 'HS256';

    public function __construct(private readonly string $secret)
    {
    }

    public function issue(array $claims, int $ttlSeconds): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
            'jti' => bin2hex(random_bytes(8)),
        ]);

        return FirebaseJwt::encode($payload, $this->secret, self::ALGO);
    }

    /**
     * @return array<string,mixed>
     */
    public function decode(string $token): array
    {
        try {
            $decoded = FirebaseJwt::decode($token, new Key($this->secret, self::ALGO));
        } catch (ExpiredException) {
            throw new UnauthorizedException('Token expired', 'TOKEN_EXPIRED');
        } catch (\Throwable) {
            throw new UnauthorizedException('Invalid token');
        }

        return (array) $decoded;
    }
}
