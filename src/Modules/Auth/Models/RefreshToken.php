<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

final class RefreshToken
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly string $jti,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly ?\DateTimeImmutable $revokedAt,
        public readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (int) $row['user_id'],
            (string) $row['jti'],
            new \DateTimeImmutable((string) $row['expires_at']),
            $row['revoked_at'] !== null ? new \DateTimeImmutable((string) $row['revoked_at']) : null,
            new \DateTimeImmutable((string) $row['created_at']),
        );
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }
}
