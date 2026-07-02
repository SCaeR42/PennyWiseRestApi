<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers\V1;

use App\Core\Exceptions\BadRequestException;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Users\DTO\RegisterUserDTO;
use App\Modules\Users\DTO\UpdateUserDTO;
use App\Modules\Users\Services\UserService;
use App\Modules\Users\Validators\UserValidator;

final class UsersController
{
    public function __construct(
        private readonly UserService $service,
        private readonly UserValidator $validator,
    ) {
    }

    public function register(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateRegister($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $user = $this->service->register(new RegisterUserDTO(
            (string) $data['email'],
            (string) $data['password'],
            (string) $data['name'],
        ));

        return Response::success($user->toArray(), null, 201);
    }

    public function profile(Request $request): Response
    {
        $user = $this->service->getProfile($request->userId());

        return Response::success($user->toArray());
    }

    public function updateProfile(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateUpdate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $user = $this->service->updateProfile($request->userId(), new UpdateUserDTO(
            $data['name'] ?? null,
            $data['email'] ?? null,
        ));

        return Response::success($user->toArray());
    }

    public function deleteAccount(Request $request): Response
    {
        $this->service->deleteAccount($request->userId());

        return Response::success(null, null, 204);
    }
}
