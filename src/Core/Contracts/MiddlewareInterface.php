<?php

declare(strict_types=1);

namespace App\Core\Contracts;

use App\Core\Request;
use App\Core\Response;

interface MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response;
}
