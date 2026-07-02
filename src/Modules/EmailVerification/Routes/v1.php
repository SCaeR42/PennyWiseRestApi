<?php

declare(strict_types=1);

use App\Modules\EmailVerification\Controllers\V1\EmailVerificationController;

/** @var App\Core\Router $router */
$router->post('/api/v1/email-verification/verify', [EmailVerificationController::class, 'verify'], ['auth']);
