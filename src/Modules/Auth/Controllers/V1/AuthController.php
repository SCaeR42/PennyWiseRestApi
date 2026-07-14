<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers\V1;

use App\Core\Exceptions\BadRequestException;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Validators\AuthValidator;

final class AuthController
{
    public function __construct(
        private readonly AuthService $service,
        private readonly AuthValidator $validator,
    ) {
    }

    public function token(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateLogin($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $tokens = $this->service->login((string) $data['email'], (string) $data['password']);

        return Response::success($tokens);
    }

    public function refresh(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateRefresh($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        $tokens = $this->service->refresh((string) $data['refresh_token']);

        return Response::success($tokens);
    }

    public function logout(Request $request): Response
    {
        // Отзывает все refresh-токены пользователя (см. AuthService::refresh() —
        // ротация + reuse-detection). Access-токен ещё доживёт свои оставшиеся
        // секунды до истечения JWT_TTL — осознанный компромисс (SDD 3.2.2):
        // проверка access-токена остаётся O(1)/stateless на каждый запрос.
        $this->service->logout($request->userId());

        return Response::success(['message' => 'Logged out']);
    }
}
