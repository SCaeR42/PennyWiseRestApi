<?php

declare(strict_types=1);

namespace App\Modules\Settings\Services;

use App\Modules\Settings\Repositories\SettingRepository;

final class SettingService
{
    public function __construct(private readonly SettingRepository $settings)
    {
    }

    public function getAll(int $userId): array
    {
        return $this->settings->getAllForUser($userId);
    }

    public function updateMany(int $userId, array $pairs): array
    {
        foreach ($pairs as $key => $value) {
            $this->settings->upsert($userId, (string) $key, $value);
        }

        return $this->getAll($userId);
    }
}
