<?php

declare(strict_types=1);

namespace App\Modules\Settings\Repositories;

final class SettingRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function getAllForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT setting_key, value FROM settings WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['setting_key']] = json_decode((string) $row['value'], true);
        }

        return $result;
    }

    public function upsert(int $userId, string $key, mixed $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (user_id, setting_key, value) VALUES (:user_id, :setting_key, :value)
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'setting_key' => $key,
            'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
