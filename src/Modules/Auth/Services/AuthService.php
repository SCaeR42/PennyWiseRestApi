<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Exceptions\UnauthorizedException;
use App\Core\Jwt;
use App\Modules\Auth\Repositories\RefreshTokenRepository;
use App\Modules\Users\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Jwt $jwt,
        private readonly RefreshTokenRepository $refreshTokens,
    ) {
    }

    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, $user->passwordHash)) {
            throw new UnauthorizedException('Invalid email or password', 'INVALID_CREDENTIALS');
        }

        return $this->issueTokenPair($user->id, $user->email);
    }

    /**
     * Refresh-токены одноразовые (ротация): валидный jti тут же помечается
     * использованным и клиенту выдаётся новая пара. Повторное предъявление
     * уже потраченного jti — верный признак кражи токена, поэтому все
     * активные сессии пользователя отзываются немедленно.
     */
    public function refresh(string $refreshToken): array
    {
        $claims = $this->jwt->decode($refreshToken);
        if (($claims['type'] ?? null) !== 'refresh') {
            throw new UnauthorizedException('A refresh token is required');
        }

        $jti = (string) ($claims['jti'] ?? '');
        $stored = $this->refreshTokens->findByJti($jti);

        if ($stored === null || $stored->isExpired()) {
            throw new UnauthorizedException('Refresh token not recognized');
        }

        if ($stored->isRevoked()) {
            $this->refreshTokens->revokeAllForUser($stored->userId);

            throw new UnauthorizedException(
                'Refresh token reuse detected — all sessions have been revoked',
                'REFRESH_TOKEN_REUSED',
            );
        }

        $user = $this->users->findById($stored->userId);
        if ($user === null) {
            throw new UnauthorizedException('User not found');
        }

        $this->refreshTokens->revoke($jti);

        return $this->issueTokenPair($user->id, $user->email);
    }

    public function logout(int $userId): void
    {
        $this->refreshTokens->revokeAllForUser($userId);
    }

    private function issueTokenPair(int $userId, string $email): array
    {
        $accessTtl = (int) ($_ENV['JWT_TTL'] ?? 900);
        $refreshTtl = (int) ($_ENV['JWT_REFRESH_TTL'] ?? 86400);

        $accessToken = $this->jwt->issue(['sub' => $userId, 'email' => $email, 'type' => 'access'], $accessTtl);

        $refreshJti = bin2hex(random_bytes(8));
        $refreshToken = $this->jwt->issue(
            ['sub' => $userId, 'email' => $email, 'type' => 'refresh', 'jti' => $refreshJti],
            $refreshTtl,
        );
        $this->refreshTokens->create(
            $userId,
            $refreshJti,
            (new \DateTimeImmutable())->modify("+{$refreshTtl} seconds"),
        );

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
        ];
    }
}
