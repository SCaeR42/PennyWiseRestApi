<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Contracts\MiddlewareInterface;
use App\Core\Contracts\ModuleInterface;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;

final class Kernel
{
    // HS256 требует ключ длиной от 32 байт (256 бит) — столько же требует
    // firebase/php-jwt при encode()/decode(). Проверяем здесь, а не там,
    // чтобы ловить неверный конфиг сразу при старте, а не на первом логине.
    private const JWT_SECRET_MIN_BYTES = 32;

    private const MIDDLEWARE_MAP = [
        'auth' => AuthMiddleware::class,
    ];

    /** @var list<class-string> */
    private const GLOBAL_MIDDLEWARE = [
        CorsMiddleware::class,
    ];

    /** @var list<string> */
    private const MODULES = [
        'Users',
        'Auth',
        'Accounts',
        'Wallets',
        'Categories',
        'Tags',
        'Transactions',
        'Settings',
        'Dashboard',
        'System',
        'EmailVerification',
    ];

    private readonly Container $container;
    private readonly Router $router;

    public function __construct()
    {
        $jwtSecret = $this->assertJwtSecretIsConfigured();

        $this->container = new Container();
        $this->router = new Router();

        $this->container->instance(Router::class, $this->router);
        $this->container->singleton(\PDO::class, static fn () => Database::connection());
        $this->container->singleton(Jwt::class, static fn () => new Jwt($jwtSecret));

        $this->loadModules();
    }

    private function assertJwtSecretIsConfigured(): string
    {
        $secret = (string) ($_ENV['JWT_SECRET'] ?? '');

        if (mb_strlen($secret, '8bit') < self::JWT_SECRET_MIN_BYTES) {
            throw new \RuntimeException(sprintf(
                'JWT_SECRET is missing or shorter than %d bytes (HS256 requires at least a 256-bit key). '
                . 'Set a proper JWT_SECRET in .env or the docker-compose environment.',
                self::JWT_SECRET_MIN_BYTES,
            ));
        }

        return $secret;
    }

    private function loadModules(): void
    {
        foreach (self::MODULES as $name) {
            /** @var class-string<ModuleInterface> $moduleClass */
            $moduleClass = "App\\Modules\\{$name}\\Module";
            /** @var ModuleInterface $module */
            $module = $this->container->make($moduleClass);
            $module->registerRoutes($this->router);
        }
    }

    public function handle(Request $request): Response
    {
        if ($request->method === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');

            return Response::success(null, null, 204);
        }

        try {
            $route = $this->router->match($request->method, $request->path);
            if ($route === null) {
                throw new Exceptions\NotFoundException('Route not found');
            }

            $request->setParams($route['params']);

            $middlewareNames = array_merge(self::GLOBAL_MIDDLEWARE, array_map(
                static fn (string $name) => self::MIDDLEWARE_MAP[$name] ?? $name,
                $route['middleware'],
            ));

            [$controllerClass, $method] = $route['handler'];
            $controller = $this->container->make($controllerClass);

            $handler = function (Request $req) use ($controller, $method) {
                return $controller->$method($req);
            };

            foreach (array_reverse($middlewareNames) as $middlewareClass) {
                /** @var MiddlewareInterface $middleware */
                $middleware = $this->container->make($middlewareClass);
                $next = $handler;
                $handler = static fn (Request $req) => $middleware->handle($req, $next);
            }

            return $handler($request);
        } catch (\Throwable $exception) {
            return (new ExceptionHandler())->handle($exception);
        }
    }
}
