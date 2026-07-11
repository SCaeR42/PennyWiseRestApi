<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\EmailVerification;

use App\Modules\EmailVerification\Services\DnsResolverStub;
use App\Modules\EmailVerification\Services\EmailFormatValidator;
use App\Modules\EmailVerification\Services\EmailVerificationService;
use App\Modules\EmailVerification\Services\MxRecordChecker;
use PHPUnit\Framework\TestCase;

final class EmailVerificationServiceTest extends TestCase
{
    private EmailVerificationService $service;

    protected function setUp(): void
    {
        $this->service = new EmailVerificationService(new EmailFormatValidator(), new MxRecordChecker());
    }

    protected function tearDown(): void
    {
        DnsResolverStub::reset();
    }

    public function testInvalidFormatSkipsDnsLookupEntirely(): void
    {
        $dnsCalls = 0;
        DnsResolverStub::fake(static function () use (&$dnsCalls): bool {
            $dnsCalls++;

            return true;
        });

        $results = $this->service->verifyBatch(['not-an-email']);

        self::assertSame('invalid_format', $results[0]['status']);
        self::assertFalse($results[0]['valid']);
        self::assertNull($results[0]['domain']);
        self::assertSame(0, $dnsCalls, 'MxRecordChecker must not be invoked for a malformed email');
    }

    public function testValidFormatTriggersDnsLookup(): void
    {
        DnsResolverStub::fake(static fn (string $host, string $type): bool => $type === 'MX');

        $results = $this->service->verifyBatch(['user@example.com']);

        self::assertSame('valid', $results[0]['status']);
        self::assertTrue($results[0]['valid']);
        self::assertSame('example.com', $results[0]['domain']);
    }

    public function testMixedBatchOnlyQueriesDnsForWellFormedDomains(): void
    {
        $queriedDomains = [];
        DnsResolverStub::fake(static function (string $host, string $type) use (&$queriedDomains): bool {
            $queriedDomains[] = $host;

            return $type === 'MX';
        });

        $results = $this->service->verifyBatch(['invalid-email', 'user@example.com', '@bad', 'other@example.org']);

        self::assertSame(['example.com', 'example.org'], $queriedDomains);
        self::assertSame(
            ['invalid_format', 'valid', 'invalid_format', 'valid'],
            array_column($results, 'status'),
        );
    }
}
