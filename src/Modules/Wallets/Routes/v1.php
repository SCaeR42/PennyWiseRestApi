<?php

declare(strict_types=1);

use App\Modules\Wallets\Controllers\V1\WalletsController;

/** @var App\Core\Router $router */
$router->get('/api/v1/wallets', [WalletsController::class, 'index'], ['auth']);
$router->post('/api/v1/wallets', [WalletsController::class, 'store'], ['auth']);
$router->get('/api/v1/wallets/{id}', [WalletsController::class, 'show'], ['auth']);
$router->put('/api/v1/wallets/{id}', [WalletsController::class, 'update'], ['auth']);
$router->delete('/api/v1/wallets/{id}', [WalletsController::class, 'destroy'], ['auth']);
$router->get('/api/v1/wallets/{id}/balance', [WalletsController::class, 'balance'], ['auth']);
