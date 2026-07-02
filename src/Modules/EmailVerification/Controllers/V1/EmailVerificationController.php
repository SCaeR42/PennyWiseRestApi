<?php

declare(strict_types=1);

namespace App\Modules\EmailVerification\Controllers\V1;

use App\Core\Exceptions\BadRequestException;
use App\Core\Request;
use App\Core\Response;
use App\Modules\EmailVerification\DTO\VerifyEmailsRequestDTO;
use App\Modules\EmailVerification\Services\EmailVerificationService;
use App\Modules\EmailVerification\Validators\VerifyEmailsRequestValidator;

final class EmailVerificationController
{
    public function __construct(
        private readonly EmailVerificationService $service,
        private readonly VerifyEmailsRequestValidator $validator,
    ) {
    }

    public function verify(Request $request): Response
    {
        $data = $request->all();
        $errors = $this->validator->validate($data);
        if ($errors !== []) {
            throw BadRequestException::validation($errors);
        }

        if (count($data['emails']) > VerifyEmailsRequestValidator::MAX_BATCH_SIZE) {
            throw new BadRequestException(
                'BATCH_TOO_LARGE',
                'Maximum ' . VerifyEmailsRequestValidator::MAX_BATCH_SIZE . ' emails per request',
            );
        }

        $dto = new VerifyEmailsRequestDTO(array_values($data['emails']));
        $results = $this->service->verifyBatch($dto->emails);

        $validCount = count(array_filter($results, static fn (array $result) => $result['valid']));

        return Response::success([
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'valid' => $validCount,
                'invalid' => count($results) - $validCount,
            ],
        ]);
    }
}
