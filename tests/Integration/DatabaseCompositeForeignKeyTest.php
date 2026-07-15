<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Modules\Accounts\Repositories\AccountRepository;
use App\Modules\Categories\Repositories\CategoryRepository;
use App\Modules\Wallets\Repositories\WalletRepository;

/**
 * Проверяет составные FK (id, user_id) напрямую через SQL, в обход
 * сервисного слоя целиком — доказывает, что защита реально есть на уровне
 * БД, а не только там, где её не забыли написать в PHP (см. миграцию
 * 010_add_tenant_scoped_composite_foreign_keys.sql).
 */
final class DatabaseCompositeForeignKeyTest extends IsolatedTransactionTestCase
{
    public function testWalletCannotReferenceAnotherUsersAccount(): void
    {
        $userA = $this->createUser('a-db-wallet-account@example.test');
        $userB = $this->createUser('b-db-wallet-account@example.test');

        $accountOfB = (new AccountRepository($this->pdo))->create($userB, 'B Account', 'bank', null, 'RUB');

        $this->expectException(\PDOException::class);

        $stmt = $this->pdo->prepare(
            'INSERT INTO wallets (user_id, account_id, name, currency) VALUES (:user_id, :account_id, :name, :currency)'
        );
        $stmt->execute([
            'user_id' => $userA,
            'account_id' => $accountOfB->id,
            'name' => 'Bypass wallet',
            'currency' => 'RUB',
        ]);
    }

    public function testCategoryCannotReferenceAnotherUsersParent(): void
    {
        $userA = $this->createUser('a-db-category-parent@example.test');
        $userB = $this->createUser('b-db-category-parent@example.test');

        $rootOfB = (new CategoryRepository($this->pdo))->create($userB, null, 'B Root', 'expense');

        $this->expectException(\PDOException::class);

        $stmt = $this->pdo->prepare(
            'INSERT INTO categories (user_id, parent_id, name, type) VALUES (:user_id, :parent_id, :name, :type)'
        );
        $stmt->execute([
            'user_id' => $userA,
            'parent_id' => $rootOfB->id,
            'name' => 'Bypass child',
            'type' => 'expense',
        ]);
    }

    public function testTransactionCannotReferenceAnotherUsersWallet(): void
    {
        $userA = $this->createUser('a-db-tx-wallet@example.test');
        $userB = $this->createUser('b-db-tx-wallet@example.test');

        $walletOfB = (new WalletRepository($this->pdo))->create($userB, null, 'B Wallet', 'RUB', false);
        $categoryOfA = (new CategoryRepository($this->pdo))->create($userA, null, 'A Category', 'expense');

        $this->expectException(\PDOException::class);

        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions (user_id, wallet_id, category_id, type, amount, date)
             VALUES (:user_id, :wallet_id, :category_id, :type, :amount, :date)'
        );
        $stmt->execute([
            'user_id' => $userA,
            'wallet_id' => $walletOfB->id,
            'category_id' => $categoryOfA->id,
            'type' => 'expense',
            'amount' => 100.00,
            'date' => '2026-01-01',
        ]);
    }

    public function testTransactionCannotReferenceAnotherUsersCategory(): void
    {
        $userA = $this->createUser('a-db-tx-category@example.test');
        $userB = $this->createUser('b-db-tx-category@example.test');

        $walletOfA = (new WalletRepository($this->pdo))->create($userA, null, 'A Wallet', 'RUB', false);
        $categoryOfB = (new CategoryRepository($this->pdo))->create($userB, null, 'B Category', 'expense');

        $this->expectException(\PDOException::class);

        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions (user_id, wallet_id, category_id, type, amount, date)
             VALUES (:user_id, :wallet_id, :category_id, :type, :amount, :date)'
        );
        $stmt->execute([
            'user_id' => $userA,
            'wallet_id' => $walletOfA->id,
            'category_id' => $categoryOfB->id,
            'type' => 'expense',
            'amount' => 100.00,
            'date' => '2026-01-01',
        ]);
    }

    public function testDeletingAccountWithLinkedWalletIsRestricted(): void
    {
        $userA = $this->createUser('a-db-account-restrict@example.test');
        $account = (new AccountRepository($this->pdo))->create($userA, 'A Account', 'bank', null, 'RUB');
        (new WalletRepository($this->pdo))->create($userA, $account->id, 'A Wallet', 'RUB', false);

        $this->expectException(\PDOException::class);

        $this->pdo->prepare('DELETE FROM accounts WHERE id = :id')->execute(['id' => $account->id]);
    }

    public function testDeletingCategoryWithChildIsRestricted(): void
    {
        $userA = $this->createUser('a-db-category-restrict@example.test');
        $categories = new CategoryRepository($this->pdo);
        $root = $categories->create($userA, null, 'A Root', 'expense');
        $categories->create($userA, $root->id, 'A Child', 'expense');

        $this->expectException(\PDOException::class);

        $this->pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $root->id]);
    }
}
