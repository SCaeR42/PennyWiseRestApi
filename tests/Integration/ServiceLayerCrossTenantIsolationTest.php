<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Core\Exceptions\BadRequestException;
use App\Modules\Accounts\Repositories\AccountRepository;
use App\Modules\Categories\DTO\CreateCategoryDTO;
use App\Modules\Categories\Repositories\CategoryRepository;
use App\Modules\Categories\Services\CategoryService;
use App\Modules\Tags\Repositories\TagRepository;
use App\Modules\Transactions\DTO\CreateTransactionDTO;
use App\Modules\Transactions\Repositories\TransactionRepository;
use App\Modules\Transactions\Services\TransactionService;
use App\Modules\Wallets\DTO\CreateWalletDTO;
use App\Modules\Wallets\Repositories\WalletRepository;
use App\Modules\Wallets\Services\WalletService;

/**
 * Регрессия на сервисный слой: findForUser()-проверки должны продолжать
 * отклонять чужие сущности. Дополняется DatabaseCompositeForeignKeyTest,
 * где те же связи проверяются в обход сервисного слоя напрямую через SQL.
 */
final class ServiceLayerCrossTenantIsolationTest extends IsolatedTransactionTestCase
{
    public function testCannotCreateWalletWithAnotherUsersAccount(): void
    {
        $userA = $this->createUser('a-wallet-account@example.test');
        $userB = $this->createUser('b-wallet-account@example.test');

        $accounts = new AccountRepository($this->pdo);
        $accountOfB = $accounts->create($userB, 'B Account', 'bank', null, 'RUB');

        $service = new WalletService($this->pdo, new WalletRepository($this->pdo), $accounts);

        $this->expectException(BadRequestException::class);
        $service->create($userA, new CreateWalletDTO('Wallet', 'RUB', $accountOfB->id, false));
    }

    public function testCannotCreateCategoryWithAnotherUsersParent(): void
    {
        $userA = $this->createUser('a-category-parent@example.test');
        $userB = $this->createUser('b-category-parent@example.test');

        $categories = new CategoryRepository($this->pdo);
        $rootOfB = $categories->create($userB, null, 'B Root', 'expense');

        $service = new CategoryService($categories);

        $this->expectException(BadRequestException::class);
        $service->create($userA, new CreateCategoryDTO('A Child', 'expense', $rootOfB->id));
    }

    public function testCannotCreateTransactionWithAnotherUsersWallet(): void
    {
        $userA = $this->createUser('a-tx-wallet@example.test');
        $userB = $this->createUser('b-tx-wallet@example.test');

        $wallets = new WalletRepository($this->pdo);
        $walletOfB = $wallets->create($userB, null, 'B Wallet', 'RUB', false);

        $categories = new CategoryRepository($this->pdo);
        $categoryOfA = $categories->create($userA, null, 'A Category', 'expense');

        $service = new TransactionService(
            $this->pdo,
            new TransactionRepository($this->pdo),
            $wallets,
            $categories,
            new TagRepository($this->pdo),
        );

        $this->expectException(BadRequestException::class);
        $service->create(
            $userA,
            new CreateTransactionDTO($walletOfB->id, $categoryOfA->id, 'expense', 100.0, null, '2026-01-01'),
        );
    }

    public function testCannotCreateTransactionWithAnotherUsersCategory(): void
    {
        $userA = $this->createUser('a-tx-category@example.test');
        $userB = $this->createUser('b-tx-category@example.test');

        $wallets = new WalletRepository($this->pdo);
        $walletOfA = $wallets->create($userA, null, 'A Wallet', 'RUB', false);

        $categories = new CategoryRepository($this->pdo);
        $categoryOfB = $categories->create($userB, null, 'B Category', 'expense');

        $service = new TransactionService(
            $this->pdo,
            new TransactionRepository($this->pdo),
            $wallets,
            $categories,
            new TagRepository($this->pdo),
        );

        $this->expectException(BadRequestException::class);
        $service->create(
            $userA,
            new CreateTransactionDTO($walletOfA->id, $categoryOfB->id, 'expense', 100.0, null, '2026-01-01'),
        );
    }
}
