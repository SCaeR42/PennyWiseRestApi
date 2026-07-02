<?php

declare(strict_types=1);

namespace App\Modules\Categories\Controllers\V1;

use App\Core\Exceptions\BadRequestException;
use App\Core\Pagination;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Categories\DTO\CreateCategoryDTO;
use App\Modules\Categories\DTO\UpdateCategoryDTO;
use App\Modules\Categories\Services\CategoryService;
use App\Modules\Categories\Validators\CategoryValidator;

final class CategoriesController
{
    public function __construct(
        private readonly CategoryService $service,
        private readonly CategoryValidator $validator,
    ) {
    }

    public function index(Request $request): Response
    {
        $page = $request->page();
        $perPage = $request->perPage();
        $result = $this->service->paginateForUser($request->userId(), $page, $perPage);

        return Response::success(
            array_map(static fn ($category) => $category->toArray(), $result['items']),
            Pagination::meta($page, $perPage, $result['total']),
        );
    }

    public function show(Request $request): Response
    {
        $category = $this->service->getForUser($request->paramInt('id'), $request->userId());

        return Response::success($category->toArray());
    }

    public function store(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateCreate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $category = $this->service->create($request->userId(), new CreateCategoryDTO(
            (string) $data['name'],
            (string) $data['type'],
            isset($data['parent_id']) ? (int) $data['parent_id'] : null,
        ));

        return Response::success($category->toArray(), null, 201);
    }

    public function update(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateUpdate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $category = $this->service->update($request->paramInt('id'), $request->userId(), new UpdateCategoryDTO(
            $data['name'] ?? null,
            $data['type'] ?? null,
            isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            array_key_exists('parent_id', $data),
        ));

        return Response::success($category->toArray());
    }

    public function destroy(Request $request): Response
    {
        $this->service->delete($request->paramInt('id'), $request->userId());

        return Response::success(null, null, 204);
    }
}
