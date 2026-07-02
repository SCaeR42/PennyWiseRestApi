<?php

declare(strict_types=1);

namespace App\Modules\Wallets\Controllers\V1;

use App\Core\Exceptions\BadRequestException;
use App\Core\Pagination;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Wallets\DTO\CreateWalletDTO;
use App\Modules\Wallets\DTO\UpdateWalletDTO;
use App\Modules\Wallets\Services\WalletService;
use App\Modules\Wallets\Validators\WalletValidator;

final class WalletsController
{
    public function __construct(
        private readonly WalletService $service,
        private readonly WalletValidator $validator,
    ) {
    }

    public function index(Request $request): Response
    {
        $page = $request->page();
        $perPage = $request->perPage();
        $result = $this->service->paginateForUser($request->userId(), $page, $perPage);

        return Response::success(
            array_map(static fn ($wallet) => $wallet->toArray(), $result['items']),
            Pagination::meta($page, $perPage, $result['total']),
        );
    }

    public function show(Request $request): Response
    {
        $wallet = $this->service->getForUser($request->paramInt('id'), $request->userId());

        return Response::success($wallet->toArray());
    }

    public function balance(Request $request): Response
    {
        $wallet = $this->service->getForUser($request->paramInt('id'), $request->userId());

        return Response::success([
            'wallet_id' => $wallet->id,
            'balance' => round($wallet->balance, 2),
            'currency' => $wallet->currency,
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateCreate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $wallet = $this->service->create($request->userId(), new CreateWalletDTO(
            (string) $data['name'],
            (string) $data['currency'],
            isset($data['account_id']) ? (int) $data['account_id'] : null,
            (bool) ($data['is_default'] ?? false),
        ));

        return Response::success($wallet->toArray(), null, 201);
    }

    public function update(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateUpdate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $wallet = $this->service->update($request->paramInt('id'), $request->userId(), new UpdateWalletDTO(
            $data['name'] ?? null,
            $data['currency'] ?? null,
            array_key_exists('is_default', $data) ? (bool) $data['is_default'] : null,
        ));

        return Response::success($wallet->toArray());
    }

    public function destroy(Request $request): Response
    {
        $this->service->delete($request->paramInt('id'), $request->userId());

        return Response::success(null, null, 204);
    }
}
