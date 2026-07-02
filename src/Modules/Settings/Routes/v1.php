<?php

declare(strict_types=1);

use App\Modules\Settings\Controllers\V1\SettingsController;

/** @var App\Core\Router $router */
$router->get('/api/v1/settings', [SettingsController::class, 'index'], ['auth']);
$router->put('/api/v1/settings', [SettingsController::class, 'update'], ['auth']);
