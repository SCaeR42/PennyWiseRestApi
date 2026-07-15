<?php

declare(strict_types=1);

namespace App\Modules\Categories\Services;

use App\Core\Exceptions\BadRequestException;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\ProcessingException;
use App\Modules\Categories\DTO\CreateCategoryDTO;
use App\Modules\Categories\DTO\UpdateCategoryDTO;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Repositories\CategoryRepository;

final class CategoryService
{
    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        return $this->categories->paginateForUser($userId, $page, $perPage);
    }

    public function getForUser(int $id, int $userId): Category
    {
        $category = $this->categories->findForUser($id, $userId);
        if ($category === null) {
            throw new NotFoundException('Category not found');
        }

        return $category;
    }

    public function create(int $userId, CreateCategoryDTO $dto): Category
    {
        if ($dto->parentId !== null) {
            $this->assertParentBelongsToUser($dto->parentId, $userId, null);
        }

        return $this->categories->create($userId, $dto->parentId, $dto->name, $dto->type);
    }

    public function update(int $id, int $userId, UpdateCategoryDTO $dto): Category
    {
        $this->getForUser($id, $userId);

        $fields = [];
        if ($dto->name !== null) {
            $fields['name'] = $dto->name;
        }
        if ($dto->type !== null) {
            $fields['type'] = $dto->type;
        }
        if ($dto->parentIdProvided) {
            if ($dto->parentId !== null) {
                $this->assertParentBelongsToUser($dto->parentId, $userId, $id);
            }
            $fields['parent_id'] = $dto->parentId;
        }

        return $this->categories->update($id, $userId, $fields);
    }

    public function delete(int $id, int $userId): void
    {
        $this->getForUser($id, $userId);

        try {
            $this->categories->delete($id, $userId);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ProcessingException(
                    'Cannot delete category: it is still referenced by child categories or transactions',
                    'CATEGORY_IN_USE',
                );
            }

            throw $e;
        }
    }

    /**
     * Вложенность категорий ограничена одним уровнем (root -> дети, без внуков).
     * Это не только продуктовое ограничение, но и структурная защита от циклов
     * A-B-C-A любой длины: цикл требует, чтобы какой-то узел одновременно был
     * и родителем, и потомком в цепочке, а тут узел не может стать родителем,
     * если у него уже есть родитель, и не может получить родителя, если у него
     * уже есть дети — вместе эти два правила не оставляют пути замкнуть цикл.
     */
    private function assertParentBelongsToUser(int $parentId, int $userId, ?int $excludeId): void
    {
        if ($parentId === $excludeId) {
            throw BadRequestException::validation(
                [['field' => 'parent_id', 'message' => 'A category cannot be its own parent']],
            );
        }

        $parent = $this->categories->findForUser($parentId, $userId);
        if ($parent === null) {
            throw BadRequestException::validation(
                [['field' => 'parent_id', 'message' => 'Parent category not found']],
            );
        }

        if ($parent->parentId !== null) {
            throw BadRequestException::validation(
                [['field' => 'parent_id', 'message' => 'Nesting is limited to one level: the chosen parent already has a parent of its own']],
            );
        }

        if ($excludeId !== null && $this->categories->hasChildren($excludeId, $userId)) {
            throw BadRequestException::validation(
                [['field' => 'parent_id', 'message' => 'This category has child categories and cannot be nested under another one']],
            );
        }
    }
}
