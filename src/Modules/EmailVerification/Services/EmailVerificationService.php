<?php

declare(strict_types=1);

namespace App\Modules\EmailVerification\Services;

final class EmailVerificationService
{
    public function __construct(
        private readonly EmailFormatValidator $formatValidator,
        private readonly MxRecordChecker $mxChecker,
    ) {
    }

    /**
     * @param list<string> $emails
     * @return list<array{email:string,domain:?string,valid:bool,status:string}>
     */
    public function verifyBatch(array $emails): array
    {
        return array_map(fn (string $email) => $this->verifyOne($email), $emails);
    }

    private function verifyOne(string $email): array
    {
        if (!$this->formatValidator->isValid($email)) {
            return ['email' => $email, 'domain' => null, 'valid' => false, 'status' => 'invalid_format'];
        }

        $domain = $this->formatValidator->extractDomain($email);
        $status = $this->mxChecker->check((string) $domain);

        return [
            'email' => $email,
            'domain' => $domain,
            'valid' => $status === 'valid',
            'status' => $status,
        ];
    }
}
