<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Переиспользуемые предикаты для валидаторов модулей (App\Modules\*\Validators).
 */
final class Validation
{
    public static function isNonEmptyString(mixed $value, int $maxLength = 255): bool
    {
        return is_string($value) && $value !== '' && mb_strlen($value) <= $maxLength;
    }

    public static function isEmail(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isNumeric(mixed $value): bool
    {
        return is_int($value) || is_float($value) || (is_string($value) && is_numeric($value));
    }

    public static function isPositiveNumber(mixed $value): bool
    {
        return self::isNumeric($value) && (float) $value > 0;
    }

    public static function isDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    public static function isBool(mixed $value): bool
    {
        return is_bool($value);
    }

    public static function isInArray(mixed $value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }

    public static function isHexColor(mixed $value): bool
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
    }

    public static function isArrayOfInt(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!is_int($item) && !(is_string($item) && ctype_digit($item))) {
                return false;
            }
        }

        return true;
    }
}
