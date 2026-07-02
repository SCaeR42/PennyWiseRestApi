<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Services;

use App\Core\Exceptions\BadRequestException;
use App\Core\Exceptions\NotFoundException;
use App\Modules\Categories\Repositories\CategoryRepository;
use App\Modules\Tags\Repositories\TagRepository;
use App\Modules\Transactions\DTO\CreateTransactionDTO;
use App\Modules\Transactions\DTO\UpdateTransactionDTO;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Transactions\Repositories\TransactionRepository;
use App\Modules\Wallets\Repositories\WalletRepository;

final class TransactionService
{
    public function __construct(
        private readonly \PDO $pdo,
        private readonly TransactionRepository $transactions,
        private readonly WalletRepository $wallets,
        private readonly CategoryRepository $categories,
        private readonly TagRepository $tags,
    ) {
    }

    public function paginateForUser(int $userId, int $page, int $perPage, array $filters = []): array
    {
        return $this->transactions->paginateForUser($userId, $page, $perPage, $filters);
    }

    public function getForUser(int $id, int $userId): Transaction
    {
        $transaction = $this->transactions->findForUser($id, $userId);
        if ($transaction === null) {
            throw new NotFoundException('Transaction not found');
        }

        return $transaction;
    }

    public function create(int $userId, CreateTransactionDTO $dto): Transaction
    {
        $this->assertWalletOwnership($dto->walletId, $userId);
        $this->assertCategoryOwnership($dto->categoryId, $userId);
        $tagIds = $this->assertTagsOwnership($dto->tagIds, $userId);

        $this->pdo->beginTransaction();
        try {
            $id = $this->transactions->create(
                $userId,
                $dto->walletId,
                $dto->categoryId,
                $dto->type,
                $dto->amount,
                $dto->description,
                $dto->date,
            );
            $this->transactions->syncTags($id, $tagIds);
            $this->wallets->recalculateBalance($dto->walletId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->getForUser($id, $userId);
    }

    public function update(int $id, int $userId, UpdateTransactionDTO $dto): Transaction
    {
        $existing = $this->getForUser($id, $userId);

        $fields = [];
        $walletIdsToRecalculate = [$existing->walletId];

        if ($dto->walletId !== null && $dto->walletId !== $existing->walletId) {
            $this->assertWalletOwnership($dto->walletId, $userId);
            $fields['wallet_id'] = $dto->walletId;
            $walletIdsToRecalculate[] = $dto->walletId;
        }

        if ($dto->categoryId !== null) {
            $this->assertCategoryOwnership($dto->categoryId, $userId);
            $fields['category_id'] = $dto->categoryId;
        }

        if ($dto->type !== null) {
            $fields['type'] = $dto->type;
        }

        if ($dto->amount !== null) {
            $fields['amount'] = $dto->amount;
        }

        if ($dto->descriptionProvided) {
            $fields['description'] = $dto->description;
        }

        if ($dto->date !== null) {
            $fields['date'] = $dto->date;
        }

        $tagIds = $dto->tagIdsProvided ? $this->assertTagsOwnership($dto->tagIds ?? [], $userId) : null;

        $this->pdo->beginTransaction();
        try {
            $this->transactions->updateFields($id, $userId, $fields);
            if ($tagIds !== null) {
                $this->transactions->syncTags($id, $tagIds);
            }
            foreach (array_unique($walletIdsToRecalculate) as $walletId) {
                $this->wallets->recalculateBalance($walletId);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $this->getForUser($id, $userId);
    }

    public function delete(int $id, int $userId): void
    {
        $existing = $this->getForUser($id, $userId);

        $this->pdo->beginTransaction();
        try {
            $this->transactions->delete($id, $userId);
            $this->wallets->recalculateBalance($existing->walletId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function assertWalletOwnership(int $walletId, int $userId): void
    {
        if ($this->wallets->findForUser($walletId, $userId) === null) {
            throw BadRequestException::validation([['field' => 'wallet_id', 'message' => 'Wallet not found']]);
        }
    }

    private function assertCategoryOwnership(int $categoryId, int $userId): void
    {
        if ($this->categories->findForUser($categoryId, $userId) === null) {
            throw BadRequestException::validation([['field' => 'category_id', 'message' => 'Category not found']]);
        }
    }

    /**
     * @param list<int> $tagIds
     * @return list<int>
     */
    private function assertTagsOwnership(array $tagIds, int $userId): array
    {
        $tagIds = array_values(array_unique(array_map('intval', $tagIds)));
        if ($tagIds === []) {
            return [];
        }

        $found = $this->tags->findManyForUser($tagIds, $userId);
        if (count($found) !== count($tagIds)) {
            throw BadRequestException::validation([['field' => 'tags', 'message' => 'One or more tags not found']]);
        }

        return array_map(static fn ($tag) => $tag->id, $found);
    }
}
