<?php

declare(strict_types=1);

namespace App\Modules\Users\Repositories;

use App\Modules\Users\Models\User;

final class UserRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : User::fromRow($row);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : User::fromRow($row);
    }

    public function create(string $email, string $passwordHash, string $name): User
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password, name) VALUES (:email, :password, :name)'
        );
        $stmt->execute(['email' => $email, 'password' => $passwordHash, 'name' => $name]);

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function update(int $id, array $fields): User
    {
        if ($fields !== []) {
            $assignments = implode(', ', array_map(static fn ($field) => "{$field} = :{$field}", array_keys($fields)));
            $stmt = $this->pdo->prepare("UPDATE users SET {$assignments} WHERE id = :id");
            $stmt->execute([...$fields, 'id' => $id]);
        }

        return $this->findById($id);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
