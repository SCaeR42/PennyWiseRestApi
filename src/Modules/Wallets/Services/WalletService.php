<?php

declare(strict_types=1);

namespace App\Modules\Wallets\Services;

use App\Core\Exceptions\BadRequestException;
use App\Core\Exceptions\NotFoundException;
use App\Modules\Accounts\Repositories\AccountRepository;
use App\Modules\Wallets\DTO\CreateWalletDTO;
use App\Modules\Wallets\DTO\UpdateWalletDTO;
use App\Modules\Wallets\Models\Wallet;
use App\Modules\Wallets\Repositories\WalletRepository;

final class WalletService
{
    public function __construct(
        private readonly WalletRepository $wallets,
        private readonly AccountRepository $accounts,
    ) {
    }

    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        return $this->wallets->paginateForUser($userId, $page, $perPage);
    }

    public function getForUser(int $id, int $userId): Wallet
    {
        $wallet = $this->wallets->findForUser($id, $userId);
        if ($wallet === null) {
            throw new NotFoundException('Wallet not found');
        }

        return $wallet;
    }

    public function create(int $userId, CreateWalletDTO $dto): Wallet
    {
        if ($dto->accountId !== null && $this->accounts->findForUser($dto->accountId, $userId) === null) {
            throw BadRequestException::validation(
                [['field' => 'account_id', 'message' => 'Account not found']],
            );
        }

        return $this->wallets->create($userId, $dto->accountId, $dto->name, $dto->currency, $dto->isDefault);
    }

    public function update(int $id, int $userId, UpdateWalletDTO $dto): Wallet
    {
        $this->getForUser($id, $userId);

        $fields = [];
        if ($dto->name !== null) {
            $fields['name'] = $dto->name;
        }
        if ($dto->currency !== null) {
            $fields['currency'] = $dto->currency;
        }
        if ($dto->isDefault !== null) {
            $fields['is_default'] = $dto->isDefault ? 1 : 0;
        }

        return $this->wallets->update($id, $userId, $fields);
    }

    public function delete(int $id, int $userId): void
    {
        $this->getForUser($id, $userId);
        $this->wallets->delete($id, $userId);
    }
}
