<?php

declare(strict_types=1);

use App\Modules\Transactions\Controllers\V1\TransactionsController;

/** @var App\Core\Router $router */
$router->get('/api/v1/transactions', [TransactionsController::class, 'index'], ['auth']);
$router->post('/api/v1/transactions', [TransactionsController::class, 'store'], ['auth']);
$router->get('/api/v1/transactions/{id}', [TransactionsController::class, 'show'], ['auth']);
$router->put('/api/v1/transactions/{id}', [TransactionsController::class, 'update'], ['auth']);
$router->delete('/api/v1/transactions/{id}', [TransactionsController::class, 'destroy'], ['auth']);
