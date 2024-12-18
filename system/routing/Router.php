<?php

namespace System\Routing;

use System\Middleware\MiddlewareAlias;

class Router
{
    protected array $routes = [];
    protected array $groups = [];

    public function group(array $attributes, callable $callback): void
    {
        $this->groups[] = $attributes;
        $callback($this);
        array_pop($this->groups);
    }

    public function get(string $uri, array $action): self
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, array $action): self
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, array $action): self
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function delete(string $uri, array $action): self
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function any(string $uri, array $action): self
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'] as $method) {
            $this->addRoute($method, $uri, $action);
        }
        return $this;
    }

    protected function addRoute(string $method, string $uri, array $action): self
    {
        $currentGroup = end($this->groups) ?: [];
        $prefix = $currentGroup['prefix'] ?? '';
        $uri = $prefix ? rtrim($prefix, '/') . '/' . ltrim($uri, '/') : $uri;

        // Merge group middleware with route-specific middleware
        $groupMiddleware = $currentGroup['middleware'] ?? [];
        $routeMiddleware = $action['middleware'] ?? [];
        $combinedMiddleware = array_merge($groupMiddleware, $routeMiddleware);

        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'action' => $action,
            'name' => $action['name'] ?? null,
            'middleware' => $combinedMiddleware,
            'params' => $action['params'] ?? [],
        ];

        return $this;
    }

    public function name(string $name): self
    {
        if (!empty($this->routes)) {
            $lastKey = array_key_last($this->routes);
            $this->routes[$lastKey]['name'] = $name;
        }
        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        if (!empty($this->routes)) {
            $lastKey = array_key_last($this->routes);
            $middleware = is_string($middleware) ? [$middleware] : $middleware;
            $this->routes[$lastKey]['middleware'] = array_merge(
                $this->routes[$lastKey]['middleware'],
                $middleware
            );
        }
        return $this;
    }

    public function params(array $params): self
    {
        if (!empty($this->routes)) {
            $lastKey = array_key_last($this->routes);
            $this->routes[$lastKey]['params'] = $params;
        }
        return $this;
    }

    public function dispatch(string $uri, string $method): void
    {
        $route = $this->findRoute($uri, $method);

        if ($route) {
            $this->handleRoute($route, $this->extractParams($route, $uri));
        } else {
            $this->handleNotFound();
        }
    }

    protected function findRoute(string $uri, string $method): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($method) && preg_match($this->getRegex($route['uri']), $uri)) {
                return $route;
            }
        }
        return null;
    }

    protected function getRegex(string $uri): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . str_replace('/', '\/', $pattern) . '$#';
    }

    protected function extractParams(array $route, string $uri): array
    {
        preg_match($this->getRegex($route['uri']), $uri, $matches);
        return array_filter($matches, fn($key) => !is_numeric($key), ARRAY_FILTER_USE_KEY);
    }

    protected function handleRoute(array $route, array $params): void
    {
        try {
            $this->runMiddleware($route['middleware']);

            [$controller, $method] = $route['action'];
            $controllerInstance = new $controller();

            if (!method_exists($controllerInstance, $method)) {
                throw new \RuntimeException("Method '$method' not found in controller '$controller'.");
            }

            $response = $controllerInstance->$method(...$params);
            $this->sendResponse($response);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }



    protected function runMiddleware(array $middlewares): bool
    {
        foreach ($middlewares as $middleware) {
            $result = MiddlewareAlias::run($middleware);
            if ($result === false) {
                return false; // Stop execution if any middleware fails
            }
        }
        return true;
    }

    protected function handleNotFound(): void
    {
        http_response_code(404);
        echo "404 Not Found";
    }

    protected function handleException(\Exception $e): void
    {
        http_response_code(403);
        echo $e->getMessage();
    }

    protected function sendResponse(mixed $response): void
    {
        if (is_string($response)) {
            echo $response;
        } elseif (is_array($response) || is_object($response)) {
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    }
}
