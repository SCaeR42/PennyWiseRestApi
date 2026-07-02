<?php

declare(strict_types=1);

namespace App\Core;

final class Pagination
{
    public static function normalizePage(int $page): int
    {
        return max(1, $page);
    }

    public static function normalizePerPage(int $perPage, int $max = 100): int
    {
        return min($max, max(1, $perPage));
    }

    public static function offset(int $page, int $perPage): int
    {
        return (self::normalizePage($page) - 1) * self::normalizePerPage($perPage);
    }

    public static function meta(int $page, int $perPage, int $total): array
    {
        return [
            'page' => self::normalizePage($page),
            'per_page' => self::normalizePerPage($perPage),
            'total' => $total,
        ];
    }
}
