<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Kernel;
use App\Core\Request;

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

$kernel = new Kernel();
$response = $kernel->handle(Request::fromGlobals());
$response->send();
