<?php

declare(strict_types=1);

namespace App\Modules\Tags\Services;

use App\Core\Exceptions\NotFoundException;
use App\Modules\Tags\DTO\CreateTagDTO;
use App\Modules\Tags\DTO\UpdateTagDTO;
use App\Modules\Tags\Models\Tag;
use App\Modules\Tags\Repositories\TagRepository;

final class TagService
{
    public function __construct(private readonly TagRepository $tags)
    {
    }

    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        return $this->tags->paginateForUser($userId, $page, $perPage);
    }

    public function getForUser(int $id, int $userId): Tag
    {
        $tag = $this->tags->findForUser($id, $userId);
        if ($tag === null) {
            throw new NotFoundException('Tag not found');
        }

        return $tag;
    }

    public function create(int $userId, CreateTagDTO $dto): Tag
    {
        return $this->tags->create($userId, $dto->name, $dto->color);
    }

    public function update(int $id, int $userId, UpdateTagDTO $dto): Tag
    {
        $this->getForUser($id, $userId);

        $fields = array_filter([
            'name' => $dto->name,
            'color' => $dto->color,
        ], static fn ($value) => $value !== null);

        return $this->tags->update($id, $userId, $fields);
    }

    public function delete(int $id, int $userId): void
    {
        $this->getForUser($id, $userId);
        $this->tags->delete($id, $userId);
    }
}
