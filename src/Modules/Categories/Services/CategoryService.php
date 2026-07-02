<?php

declare(strict_types=1);

namespace App\Modules\Categories\Services;

use App\Core\Exceptions\BadRequestException;
use App\Core\Exceptions\NotFoundException;
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
        $this->categories->delete($id, $userId);
    }

    private function assertParentBelongsToUser(int $parentId, int $userId, ?int $excludeId): void
    {
        if ($parentId === $excludeId) {
            throw BadRequestException::validation(
                [['field' => 'parent_id', 'message' => 'A category cannot be its own parent']],
            );
        }

        if ($this->categories->findForUser($parentId, $userId) === null) {
            throw BadRequestException::validation(
                [['field' => 'parent_id', 'message' => 'Parent category not found']],
            );
        }
    }
}
