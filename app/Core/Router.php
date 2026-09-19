<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:array|callable, middleware:array}> */
    private array $routes = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function patch(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, array|callable $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): mixed
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method() && $route['method'] !== 'ANY') {
                continue;
            }
            $params = $this->match($route['pattern'], $request->path());
            if ($params === null) {
                continue;
            }
            return [
                'handler' => $route['handler'],
                'params' => $params,
                'middleware' => $route['middleware'],
            ];
        }
        return null;
    }

    private function match(string $pattern, string $path): ?array
    {
        $pattern = rtrim($pattern, '/') ?: '/';
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', '(?P<$1>[^/]+)', $pattern);
        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return null;
        }
        return array_filter($matches, static fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);
    }
}
