<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\EmailVerification;

use App\Modules\EmailVerification\Services\EmailFormatValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailFormatValidatorTest extends TestCase
{
    private EmailFormatValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new EmailFormatValidator();
    }

    #[DataProvider('validEmailProvider')]
    public function testAcceptsValidEmails(string $email): void
    {
        self::assertTrue($this->validator->isValid($email));
    }

    public static function validEmailProvider(): array
    {
        return [
            ['user@gmail.com'],
            ['first.last@example.co.uk'],
            ['user+tag@example.com'],
            ['user_name@sub.example.com'],
        ];
    }

    #[DataProvider('invalidEmailProvider')]
    public function testRejectsInvalidEmails(string $email): void
    {
        self::assertFalse($this->validator->isValid($email));
    }

    public static function invalidEmailProvider(): array
    {
        return [
            ['invalid-email'],
            ['@example.com'],
            ['user@'],
            ['user@localhost'],
            ['user example.com'],
        ];
    }

    public function testExtractsDomain(): void
    {
        self::assertSame('gmail.com', $this->validator->extractDomain('user@gmail.com'));
        self::assertNull($this->validator->extractDomain('no-at-sign'));
    }
}
