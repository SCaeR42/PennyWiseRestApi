<?php

declare(strict_types=1);

use App\Modules\Tags\Controllers\V1\TagsController;

/** @var App\Core\Router $router */
$router->get('/api/v1/tags', [TagsController::class, 'index'], ['auth']);
$router->post('/api/v1/tags', [TagsController::class, 'store'], ['auth']);
$router->put('/api/v1/tags/{id}', [TagsController::class, 'update'], ['auth']);
$router->delete('/api/v1/tags/{id}', [TagsController::class, 'destroy'], ['auth']);
