<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Controllers\V1;

use App\Core\Exceptions\BadRequestException;
use App\Core\Pagination;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Accounts\DTO\CreateAccountDTO;
use App\Modules\Accounts\DTO\UpdateAccountDTO;
use App\Modules\Accounts\Services\AccountService;
use App\Modules\Accounts\Validators\AccountValidator;

final class AccountsController
{
    public function __construct(
        private readonly AccountService $service,
        private readonly AccountValidator $validator,
    ) {
    }

    public function index(Request $request): Response
    {
        $page = $request->page();
        $perPage = $request->perPage();
        $result = $this->service->paginateForUser($request->userId(), $page, $perPage);

        return Response::success(
            array_map(static fn ($account) => $account->toArray(), $result['items']),
            Pagination::meta($page, $perPage, $result['total']),
        );
    }

    public function show(Request $request): Response
    {
        $account = $this->service->getForUser($request->paramInt('id'), $request->userId());

        return Response::success($account->toArray());
    }

    public function store(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateCreate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $account = $this->service->create($request->userId(), new CreateAccountDTO(
            (string) $data['name'],
            (string) $data['type'],
            $data['requisites'] ?? null,
            (string) $data['currency'],
        ));

        return Response::success($account->toArray(), null, 201);
    }

    public function update(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateUpdate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $account = $this->service->update($request->paramInt('id'), $request->userId(), new UpdateAccountDTO(
            $data['name'] ?? null,
            $data['type'] ?? null,
            $data['requisites'] ?? null,
            $data['currency'] ?? null,
        ));

        return Response::success($account->toArray());
    }

    public function destroy(Request $request): Response
    {
        $this->service->delete($request->paramInt('id'), $request->userId());

        return Response::success(null, null, 204);
    }
}
