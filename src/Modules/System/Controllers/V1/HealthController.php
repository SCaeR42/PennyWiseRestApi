<?php

declare(strict_types=1);

namespace App\Modules\System\Controllers\V1;

use App\Core\Request;
use App\Core\Response;

final class HealthController
{
    private const STARTED_AT_FILE = '/tmp/started_at';

    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function show(Request $request): Response
    {
        $dbStatus = 'ok';
        try {
            $this->pdo->query('SELECT 1');
        } catch (\Throwable) {
            $dbStatus = 'error';
        }

        return Response::success([
            'status' => 'ok',
            'instance' => gethostname() ?: 'unknown',
            'db' => $dbStatus,
            'uptime' => $this->uptimeSeconds(),
        ]);
    }

    private function uptimeSeconds(): int
    {
        if (!is_file(self::STARTED_AT_FILE)) {
            return 0;
        }

        $startedAt = (int) trim((string) file_get_contents(self::STARTED_AT_FILE));

        return $startedAt > 0 ? max(0, time() - $startedAt) : 0;
    }
}
