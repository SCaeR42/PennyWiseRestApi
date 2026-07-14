<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Kernel;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    private string|false $originalJwtSecret;

    protected function setUp(): void
    {
        $this->originalJwtSecret = $_ENV['JWT_SECRET'] ?? false;
    }

    protected function tearDown(): void
    {
        if ($this->originalJwtSecret === false) {
            unset($_ENV['JWT_SECRET']);
        } else {
            $_ENV['JWT_SECRET'] = $this->originalJwtSecret;
        }
    }

    public function testThrowsWhenJwtSecretIsMissing(): void
    {
        unset($_ENV['JWT_SECRET']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/JWT_SECRET/');

        new Kernel();
    }

    public function testThrowsWhenJwtSecretIsShorterThan32Bytes(): void
    {
        $_ENV['JWT_SECRET'] = 'too-short';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/JWT_SECRET/');

        new Kernel();
    }

    public function testBootsWhenJwtSecretIsAtLeast32Bytes(): void
    {
        $_ENV['JWT_SECRET'] = str_repeat('a', 32);

        self::assertInstanceOf(Kernel::class, new Kernel());
    }
}
