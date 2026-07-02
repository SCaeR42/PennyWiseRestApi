<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Validation;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function testIsEmail(): void
    {
        self::assertTrue(Validation::isEmail('user@example.com'));
        self::assertFalse(Validation::isEmail('not-an-email'));
        self::assertFalse(Validation::isEmail(null));
    }

    public function testIsPositiveNumber(): void
    {
        self::assertTrue(Validation::isPositiveNumber(1500.00));
        self::assertTrue(Validation::isPositiveNumber('12.5'));
        self::assertFalse(Validation::isPositiveNumber(0));
        self::assertFalse(Validation::isPositiveNumber(-5));
        self::assertFalse(Validation::isPositiveNumber('abc'));
    }

    public function testIsDate(): void
    {
        self::assertTrue(Validation::isDate('2026-06-29'));
        self::assertFalse(Validation::isDate('2026-13-01'));
        self::assertFalse(Validation::isDate('29-06-2026'));
        self::assertFalse(Validation::isDate('not-a-date'));
    }

    public function testIsHexColor(): void
    {
        self::assertTrue(Validation::isHexColor('#A1B2C3'));
        self::assertFalse(Validation::isHexColor('#ZZZZZZ'));
        self::assertFalse(Validation::isHexColor('A1B2C3'));
    }

    public function testIsArrayOfInt(): void
    {
        self::assertTrue(Validation::isArrayOfInt([1, 2, 3]));
        self::assertTrue(Validation::isArrayOfInt(['1', '2']));
        self::assertFalse(Validation::isArrayOfInt([1, 'a']));
        self::assertFalse(Validation::isArrayOfInt('not-an-array'));
    }
}
