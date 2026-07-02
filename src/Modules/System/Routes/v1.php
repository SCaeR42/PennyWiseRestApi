<?php

declare(strict_types=1);

use App\Modules\System\Controllers\V1\HealthController;

/** @var App\Core\Router $router */
$router->get('/api/v1/health', [HealthController::class, 'show']);
