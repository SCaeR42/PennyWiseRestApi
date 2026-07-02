<?php

declare(strict_types=1);

namespace App\Modules\Tags\Controllers\V1;

use App\Core\Exceptions\BadRequestException;
use App\Core\Pagination;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Tags\DTO\CreateTagDTO;
use App\Modules\Tags\DTO\UpdateTagDTO;
use App\Modules\Tags\Services\TagService;
use App\Modules\Tags\Validators\TagValidator;

final class TagsController
{
    public function __construct(
        private readonly TagService $service,
        private readonly TagValidator $validator,
    ) {
    }

    public function index(Request $request): Response
    {
        $page = $request->page();
        $perPage = $request->perPage();
        $result = $this->service->paginateForUser($request->userId(), $page, $perPage);

        return Response::success(
            array_map(static fn ($tag) => $tag->toArray(), $result['items']),
            Pagination::meta($page, $perPage, $result['total']),
        );
    }

    public function store(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateCreate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $tag = $this->service->create($request->userId(), new CreateTagDTO(
            (string) $data['name'],
            (string) ($data['color'] ?? '#CCCCCC'),
        ));

        return Response::success($tag->toArray(), null, 201);
    }

    public function update(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateUpdate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $tag = $this->service->update($request->paramInt('id'), $request->userId(), new UpdateTagDTO(
            $data['name'] ?? null,
            $data['color'] ?? null,
        ));

        return Response::success($tag->toArray());
    }

    public function destroy(Request $request): Response
    {
        $this->service->delete($request->paramInt('id'), $request->userId());

        return Response::success(null, null, 204);
    }
}
