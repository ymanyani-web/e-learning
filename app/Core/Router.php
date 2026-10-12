<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path = $this->normalize(Request::path());

        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            Response::abort(404, 'Page not found');
        }

        if (is_array($handler)) {
            [$class, $methodName] = $handler;
            $controller = new $class();
            $controller->{$methodName}();
            return;
        }

        $handler();
    }

    private function normalize(string $path): string
    {
        if ($path === '/') {
            return '/';
        }

        return '/' . trim($path, '/');
    }
}
