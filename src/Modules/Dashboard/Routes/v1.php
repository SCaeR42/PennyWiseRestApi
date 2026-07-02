<?php

declare(strict_types=1);

use App\Modules\Dashboard\Controllers\V1\DashboardController;

/** @var App\Core\Router $router */
$router->get('/api/v1/dashboard/widgets', [DashboardController::class, 'list'], ['auth']);
$router->get('/api/v1/dashboard/widgets/{name}', [DashboardController::class, 'show'], ['auth']);
