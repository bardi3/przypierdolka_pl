<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\ModeratorMiddleware;

/**
 * Router - dopasowuje URI do tras, uruchamia middleware i wywołuje kontroler.
 */
final class Router
{
    private App $app;

    /** @var array<int, array{method:string, regex:string, params:array<int,string>, handler:string, middleware:?string}> */
    private array $routes = [];

    /** @var array<string, class-string> */
    private array $middlewareMap = [
        'auth'      => AuthMiddleware::class,
        'guest'     => GuestMiddleware::class,
        'admin'     => AdminMiddleware::class,
        'moderator' => ModeratorMiddleware::class,
    ];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function loadRoutes(string $file): void
    {
        $routes = require $file;
        foreach ($routes as $route) {
            $this->add($route[0], $route[1], $route[2], $route[3] ?? null);
        }
    }

    public function add(string $method, string $pattern, string $handler, ?string $middleware = null): void
    {
        [$regex, $params] = $this->compile($pattern);
        $this->routes[] = [
            'method'     => strtoupper($method),
            'regex'      => $regex,
            'params'     => $params,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * @return array{0:string, 1:array<int,string>}
     */
    private function compile(string $pattern): array
    {
        $params = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                // page/id => liczby, reszta => segment bez ukośnika
                if (in_array($m[1], ['id', 'page'], true)) {
                    return '(\d+)';
                }
                return '([^/]+)';
            },
            $pattern
        );

        return ['#^' . $regex . '$#', $params];
    }

    public function dispatch(string $method, string $uri): Response
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['params'] as $i => $name) {
                $params[$name] = $matches[$i] ?? null;
            }

            // Middleware
            if ($route['middleware'] !== null) {
                $response = $this->runMiddleware($route['middleware']);
                if ($response !== null) {
                    return $response;
                }
            }

            return $this->invoke($route['handler'], $params);
        }

        throw HttpException::notFound("Brak trasy dla {$method} {$uri}");
    }

    private function runMiddleware(string $name): ?Response
    {
        if (!isset($this->middlewareMap[$name])) {
            return null;
        }
        $class = $this->middlewareMap[$name];
        /** @var \App\Middleware\MiddlewareInterface $middleware */
        $middleware = new $class();
        return $middleware->handle($this->app);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function invoke(string $handler, array $params): Response
    {
        [$controllerName, $action] = explode('@', $handler, 2);

        // Namespace: 'Admin\\Foo' lub 'Foo'
        $class = 'App\\Controllers\\' . str_replace('/', '\\', $controllerName);

        if (!class_exists($class)) {
            throw HttpException::notFound("Kontroler nie istnieje: {$class}");
        }

        $controller = new $class($this->app);

        if (!method_exists($controller, $action)) {
            throw HttpException::notFound("Akcja nie istnieje: {$class}::{$action}");
        }

        $result = $controller->{$action}(...array_values($params));

        if ($result instanceof Response) {
            return $result;
        }
        if (is_string($result)) {
            return Response::html($result);
        }
        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html('');
    }
}
