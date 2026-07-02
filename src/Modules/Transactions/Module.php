<?php

declare(strict_types=1);

namespace App\Modules\Transactions;

use App\Core\Contracts\ModuleInterface;
use App\Core\Router;

final class Module implements ModuleInterface
{
    public function registerRoutes(Router $router): void
    {
        require __DIR__ . '/Routes/v1.php';
    }
}
