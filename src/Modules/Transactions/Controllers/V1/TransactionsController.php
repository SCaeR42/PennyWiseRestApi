<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Controllers\V1;

use App\Core\Exceptions\BadRequestException;
use App\Core\Pagination;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Transactions\DTO\CreateTransactionDTO;
use App\Modules\Transactions\DTO\UpdateTransactionDTO;
use App\Modules\Transactions\Services\TransactionService;
use App\Modules\Transactions\Validators\TransactionValidator;

final class TransactionsController
{
    public function __construct(
        private readonly TransactionService $service,
        private readonly TransactionValidator $validator,
    ) {
    }

    public function index(Request $request): Response
    {
        $page = $request->page();
        $perPage = $request->perPage();

        $filters = array_filter([
            'wallet_id' => $request->query('wallet_id'),
            'category_id' => $request->query('category_id'),
            'type' => $request->query('type'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ], static fn ($value) => $value !== null);

        $result = $this->service->paginateForUser($request->userId(), $page, $perPage, $filters);

        return Response::success(
            array_map(static fn ($transaction) => $transaction->toArray(), $result['items']),
            Pagination::meta($page, $perPage, $result['total']),
        );
    }

    public function show(Request $request): Response
    {
        $transaction = $this->service->getForUser($request->paramInt('id'), $request->userId());

        return Response::success($transaction->toArray());
    }

    public function store(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateCreate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $transaction = $this->service->create($request->userId(), new CreateTransactionDTO(
            (int) $data['wallet_id'],
            (int) $data['category_id'],
            (string) $data['type'],
            (float) $data['amount'],
            $data['description'] ?? null,
            (string) $data['date'],
            array_map('intval', $data['tags'] ?? []),
        ));

        return Response::success($transaction->toArray(), null, 201);
    }

    public function update(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateUpdate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $transaction = $this->service->update($request->paramInt('id'), $request->userId(), new UpdateTransactionDTO(
            isset($data['wallet_id']) ? (int) $data['wallet_id'] : null,
            isset($data['category_id']) ? (int) $data['category_id'] : null,
            $data['type'] ?? null,
            isset($data['amount']) ? (float) $data['amount'] : null,
            $data['description'] ?? null,
            array_key_exists('description', $data),
            $data['date'] ?? null,
            isset($data['tags']) ? array_map('intval', $data['tags']) : null,
            array_key_exists('tags', $data),
        ));

        return Response::success($transaction->toArray());
    }

    public function destroy(Request $request): Response
    {
        $this->service->delete($request->paramInt('id'), $request->userId());

        return Response::success(null, null, 204);
    }
}
