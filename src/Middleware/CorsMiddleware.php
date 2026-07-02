<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

final class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next): Response
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        return $next($request);
    }
}
