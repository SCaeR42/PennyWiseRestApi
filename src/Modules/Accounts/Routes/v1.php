<?php

declare(strict_types=1);

use App\Modules\Accounts\Controllers\V1\AccountsController;

/** @var App\Core\Router $router */
$router->get('/api/v1/accounts', [AccountsController::class, 'index'], ['auth']);
$router->post('/api/v1/accounts', [AccountsController::class, 'store'], ['auth']);
$router->get('/api/v1/accounts/{id}', [AccountsController::class, 'show'], ['auth']);
$router->put('/api/v1/accounts/{id}', [AccountsController::class, 'update'], ['auth']);
$router->delete('/api/v1/accounts/{id}', [AccountsController::class, 'destroy'], ['auth']);
