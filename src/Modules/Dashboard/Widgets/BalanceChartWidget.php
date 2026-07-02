<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Widgets;

use App\Modules\Wallets\Repositories\WalletRepository;

final class BalanceChartWidget implements WidgetInterface
{
    public function __construct(private readonly WalletRepository $wallets)
    {
    }

    public function name(): string
    {
        return 'balance-chart';
    }

    public function data(int $userId): array
    {
        $result = $this->wallets->paginateForUser($userId, 1, 100);

        return array_map(static fn ($wallet) => [
            'wallet_id' => $wallet->id,
            'name' => $wallet->name,
            'currency' => $wallet->currency,
            'balance' => round($wallet->balance, 2),
        ], $result['items']);
    }
}
