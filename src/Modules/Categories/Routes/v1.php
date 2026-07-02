<?php

declare(strict_types=1);

use App\Modules\Categories\Controllers\V1\CategoriesController;

/** @var App\Core\Router $router */
$router->get('/api/v1/categories', [CategoriesController::class, 'index'], ['auth']);
$router->post('/api/v1/categories', [CategoriesController::class, 'store'], ['auth']);
$router->get('/api/v1/categories/{id}', [CategoriesController::class, 'show'], ['auth']);
$router->put('/api/v1/categories/{id}', [CategoriesController::class, 'update'], ['auth']);
$router->delete('/api/v1/categories/{id}', [CategoriesController::class, 'destroy'], ['auth']);
