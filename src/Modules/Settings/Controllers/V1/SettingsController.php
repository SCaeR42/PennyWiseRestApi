<?php

declare(strict_types=1);

namespace App\Modules\Settings\Controllers\V1;

use App\Core\Exceptions\BadRequestException;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Settings\Services\SettingService;
use App\Modules\Settings\Validators\SettingsValidator;

final class SettingsController
{
    public function __construct(
        private readonly SettingService $service,
        private readonly SettingsValidator $validator,
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::success($this->service->getAll($request->userId()));
    }

    public function update(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validateUpdate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        return Response::success($this->service->updateMany($request->userId(), $data));
    }
}
