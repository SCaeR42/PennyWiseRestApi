<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use App\Modules\Auth\Models\RefreshToken;

final class RefreshTokenRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function create(int $userId, string $jti, \DateTimeImmutable $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO refresh_tokens (user_id, jti, expires_at) VALUES (:user_id, :jti, :expires_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'jti' => $jti,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByJti(string $jti): ?RefreshToken
    {
        $stmt = $this->pdo->prepare('SELECT * FROM refresh_tokens WHERE jti = :jti');
        $stmt->execute(['jti' => $jti]);
        $row = $stmt->fetch();

        return $row === false ? null : RefreshToken::fromRow($row);
    }

    /**
     * Помечает конкретный refresh-токен использованным (ротация после /auth/refresh).
     */
    public function revoke(string $jti): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE refresh_tokens SET revoked_at = NOW() WHERE jti = :jti AND revoked_at IS NULL'
        );
        $stmt->execute(['jti' => $jti]);
    }

    /**
     * Отзывает все активные refresh-токены пользователя — logout "везде" и
     * реакция на обнаруженное повторное предъявление уже использованного токена.
     */
    public function revokeAllForUser(int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE refresh_tokens SET revoked_at = NOW() WHERE user_id = :user_id AND revoked_at IS NULL'
        );
        $stmt->execute(['user_id' => $userId]);
    }
}
