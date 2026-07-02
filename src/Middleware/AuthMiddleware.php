<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Exceptions\UnauthorizedException;
use App\Core\Jwt;
use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Jwt $jwt)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        $token = $request->bearerToken();
        if ($token === null) {
            throw new UnauthorizedException('Authorization token is missing');
        }

        $claims = $this->jwt->decode($token);
        if (($claims['type'] ?? 'access') !== 'access') {
            throw new UnauthorizedException('Access token required');
        }

        $request->setUser($claims);

        return $next($request);
    }
}
