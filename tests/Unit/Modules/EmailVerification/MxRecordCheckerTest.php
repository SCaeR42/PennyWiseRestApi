<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\EmailVerification;

use App\Modules\EmailVerification\Services\DnsResolverStub;
use App\Modules\EmailVerification\Services\MxRecordChecker;
use PHPUnit\Framework\TestCase;

final class MxRecordCheckerTest extends TestCase
{
    protected function tearDown(): void
    {
        DnsResolverStub::reset();
    }

    public function testReturnsValidWhenMxRecordExists(): void
    {
        DnsResolverStub::fake(
            static fn (string $host, string $type): bool => $host === 'gmail.com' && $type === 'MX'
        );

        self::assertSame('valid', (new MxRecordChecker())->check('gmail.com'));
    }

    public function testFallsBackToARecordWhenNoMxRecord(): void
    {
        DnsResolverStub::fake(static fn (string $host, string $type): bool => $type === 'A');

        self::assertSame('valid', (new MxRecordChecker())->check('a-record-only.example'));
    }

    public function testReturnsNoMxRecordWhenNothingResolves(): void
    {
        DnsResolverStub::fake(static fn (): bool => false);

        self::assertSame('no_mx_record', (new MxRecordChecker())->check('nowhere.invalid'));
    }

    public function testReturnsLookupFailedWhenResolverEmitsWarning(): void
    {
        DnsResolverStub::fake(static function (): bool {
            trigger_error('simulated DNS timeout', E_USER_WARNING);

            return false;
        });

        self::assertSame('lookup_failed', (new MxRecordChecker())->check('timeout.example'));
    }

    public function testCachesResultPerDomainWithinTheSameInstance(): void
    {
        $calls = 0;
        DnsResolverStub::fake(static function () use (&$calls): bool {
            $calls++;

            return true;
        });

        $checker = new MxRecordChecker();
        $checker->check('gmail.com');
        $checker->check('GMAIL.COM');

        self::assertSame(1, $calls, 'domain lookups should be cached case-insensitively');
    }
}
