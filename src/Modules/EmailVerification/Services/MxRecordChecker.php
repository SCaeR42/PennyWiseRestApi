<?php

declare(strict_types=1);

namespace App\Modules\EmailVerification\Services;

/**
 * DNS MX-проверка домена с fallback на A/AAAA (RFC 5321) и кэшем на домен
 * в рамках одного batch-запроса (см. SDD 3.2.11). Таймаут резолва
 * настраивается на уровне резолвера (Docker dns_opt), а не здесь —
 * PHP-функции DNS не принимают таймаут per-call.
 */
final class MxRecordChecker
{
    /** @var array<string, string> */
    private array $cache = [];

    public function check(string $domain): string
    {
        $domain = strtolower($domain);

        if (isset($this->cache[$domain])) {
            return $this->cache[$domain];
        }

        return $this->cache[$domain] = $this->resolve($domain);
    }

    private function resolve(string $domain): string
    {
        $hadError = false;
        set_error_handler(static function () use (&$hadError): bool {
            $hadError = true;

            return true;
        });

        try {
            if (checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A') || checkdnsrr($domain, 'AAAA')) {
                return 'valid';
            }
        } catch (\Throwable) {
            $hadError = true;
        } finally {
            restore_error_handler();
        }

        return $hadError ? 'lookup_failed' : 'no_mx_record';
    }
}
