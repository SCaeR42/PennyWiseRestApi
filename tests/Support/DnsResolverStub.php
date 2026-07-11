<?php

declare(strict_types=1);

// Объявлен в неймспейсе MxRecordChecker, а не в Tests\ — вызов checkdnsrr() без
// начального "\" сначала ищет функцию в текущем неймспейсе и только потом
// откатывается на глобальную. Файл подключается исключительно через
// autoload-dev (composer.json "files"), поэтому в production-автозагрузке
// (composer install --no-dev) его не существует и вызовы всегда идут в
// настоящий checkdnsrr().
namespace App\Modules\EmailVerification\Services;

final class DnsResolverStub
{
    /** @var null|\Closure(string, string): bool */
    private static ?\Closure $handler = null;

    public static function fake(\Closure $handler): void
    {
        self::$handler = $handler;
    }

    public static function reset(): void
    {
        self::$handler = null;
    }

    public static function resolve(string $host, string $type): bool
    {
        return self::$handler !== null
            ? (self::$handler)($host, $type)
            : \checkdnsrr($host, $type);
    }
}

function checkdnsrr(string $host, string $type = ''): bool
{
    return DnsResolverStub::resolve($host, $type);
}
