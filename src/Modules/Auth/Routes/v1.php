<?php

declare(strict_types=1);

use App\Modules\Auth\Controllers\V1\AuthController;

/** @var App\Core\Router $router */
$router->post('/api/v1/auth/token', [AuthController::class, 'token']);
$router->post('/api/v1/auth/refresh', [AuthController::class, 'refresh']);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout'], ['auth']);
