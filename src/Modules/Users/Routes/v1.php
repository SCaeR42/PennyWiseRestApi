<?php

declare(strict_types=1);

use App\Modules\Users\Controllers\V1\UsersController;

/** @var App\Core\Router $router */
$router->post('/api/v1/users/register', [UsersController::class, 'register']);
$router->get('/api/v1/users/profile', [UsersController::class, 'profile'], ['auth']);
$router->put('/api/v1/users/profile', [UsersController::class, 'updateProfile'], ['auth']);
$router->delete('/api/v1/users/profile', [UsersController::class, 'deleteAccount'], ['auth']);
