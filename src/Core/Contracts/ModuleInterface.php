<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\Core\Router;

interface ModuleInterface
{
    public function registerRoutes(Router $router): void;
}
