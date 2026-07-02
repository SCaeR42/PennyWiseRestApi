<?php

declare(strict_types=1);

namespace App\Modules\Users\Services;

use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ProcessingException;
use App\Modules\Users\DTO\RegisterUserDTO;
use App\Modules\Users\DTO\UpdateUserDTO;
use App\Modules\Users\Models\User;
use App\Modules\Users\Repositories\UserRepository;

final class UserService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function register(RegisterUserDTO $dto): User
    {
        if ($this->users->findByEmail($dto->email) !== null) {
            throw new ProcessingException('Email is already registered', 'EMAIL_TAKEN');
        }

        $passwordHash = password_hash($dto->password, PASSWORD_BCRYPT);

        return $this->users->create($dto->email, $passwordHash, $dto->name);
    }

    public function getProfile(int $userId): User
    {
        $user = $this->users->findById($userId);
        if ($user === null) {
            throw new NotFoundException('User not found');
        }

        return $user;
    }

    public function updateProfile(int $userId, UpdateUserDTO $dto): User
    {
        $fields = [];

        if ($dto->name !== null) {
            $fields['name'] = $dto->name;
        }

        if ($dto->email !== null) {
            $existing = $this->users->findByEmail($dto->email);
            if ($existing !== null && $existing->id !== $userId) {
                throw new ProcessingException('Email is already registered', 'EMAIL_TAKEN');
            }
            $fields['email'] = $dto->email;
        }

        if ($fields === []) {
            return $this->getProfile($userId);
        }

        return $this->users->update($userId, $fields);
    }

    public function deleteAccount(int $userId): void
    {
        $this->users->delete($userId);
    }
}
