<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Core\Exceptions\UnauthorizedException;
use App\Core\Jwt;
use App\Modules\Users\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Jwt $jwt,
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

    public function refresh(string $refreshToken): array
    {
        $claims = $this->jwt->decode($refreshToken);
        if (($claims['type'] ?? null) !== 'refresh') {
            throw new UnauthorizedException('A refresh token is required');
        }

        $user = $this->users->findById((int) $claims['sub']);
        if ($user === null) {
            throw new UnauthorizedException('User not found');
        }

        return $this->issueTokenPair($user->id, $user->email);
    }

    private function issueTokenPair(int $userId, string $email): array
    {
        $accessTtl = (int) ($_ENV['JWT_TTL'] ?? 3600);
        $refreshTtl = (int) ($_ENV['JWT_REFRESH_TTL'] ?? 604800);

        $accessToken = $this->jwt->issue(['sub' => $userId, 'email' => $email, 'type' => 'access'], $accessTtl);
        $refreshToken = $this->jwt->issue(['sub' => $userId, 'email' => $email, 'type' => 'refresh'], $refreshTtl);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
        ];
    }
}
