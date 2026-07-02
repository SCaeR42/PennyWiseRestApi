<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Services;

use App\Core\Exceptions\NotFoundException;
use App\Modules\Accounts\DTO\CreateAccountDTO;
use App\Modules\Accounts\DTO\UpdateAccountDTO;
use App\Modules\Accounts\Models\Account;
use App\Modules\Accounts\Repositories\AccountRepository;

final class AccountService
{
    public function __construct(private readonly AccountRepository $accounts)
    {
    }

    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        return $this->accounts->paginateForUser($userId, $page, $perPage);
    }

    public function getForUser(int $id, int $userId): Account
    {
        $account = $this->accounts->findForUser($id, $userId);
        if ($account === null) {
            throw new NotFoundException('Account not found');
        }

        return $account;
    }

    public function create(int $userId, CreateAccountDTO $dto): Account
    {
        return $this->accounts->create($userId, $dto->name, $dto->type, $dto->requisites, $dto->currency);
    }

    public function update(int $id, int $userId, UpdateAccountDTO $dto): Account
    {
        $this->getForUser($id, $userId);

        $fields = array_filter([
            'name' => $dto->name,
            'type' => $dto->type,
            'requisites' => $dto->requisites,
            'currency' => $dto->currency,
        ], static fn ($value) => $value !== null);

        return $this->accounts->update($id, $userId, $fields);
    }

    public function delete(int $id, int $userId): void
    {
        $this->getForUser($id, $userId);
        $this->accounts->delete($id, $userId);
    }
}
