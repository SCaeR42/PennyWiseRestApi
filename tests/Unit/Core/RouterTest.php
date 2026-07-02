<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testMatchesStaticRoute(): void
    {
        $router = new Router();
        $router->get('/api/v1/wallets', ['WalletsController', 'index']);

        $match = $router->match('GET', '/api/v1/wallets');

        self::assertNotNull($match);
        self::assertSame(['WalletsController', 'index'], $match['handler']);
        self::assertSame([], $match['params']);
    }

    public function testMatchesRouteWithParams(): void
    {
        $router = new Router();
        $router->get('/api/v1/wallets/{id}', ['WalletsController', 'show']);

        $match = $router->match('GET', '/api/v1/wallets/42');

        self::assertNotNull($match);
        self::assertSame('42', $match['params']['id']);
    }

    public function testDistinguishesNestedRouteFromParametrizedOne(): void
    {
        $router = new Router();
        $router->get('/api/v1/wallets/{id}', ['WalletsController', 'show']);
        $router->get('/api/v1/wallets/{id}/balance', ['WalletsController', 'balance']);

        $show = $router->match('GET', '/api/v1/wallets/7');
        $balance = $router->match('GET', '/api/v1/wallets/7/balance');

        self::assertSame('show', $show['handler'][1]);
        self::assertSame('balance', $balance['handler'][1]);
    }

    public function testReturnsNullWhenMethodDoesNotMatch(): void
    {
        $router = new Router();
        $router->get('/api/v1/wallets', ['WalletsController', 'index']);

        self::assertNull($router->match('POST', '/api/v1/wallets'));
    }

    public function testReturnsNullWhenPathDoesNotMatch(): void
    {
        $router = new Router();
        $router->get('/api/v1/wallets', ['WalletsController', 'index']);

        self::assertNull($router->match('GET', '/api/v1/unknown'));
    }
}
