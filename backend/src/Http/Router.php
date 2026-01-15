<?php

namespace App\Http;

use App\Services\AuthService;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, bool $public = false): void
    {
        $this->add('GET', $path, $handler, $public);
    }

    public function post(string $path, callable $handler, bool $public = false): void
    {
        $this->add('POST', $path, $handler, $public);
    }

    public function put(string $path, callable $handler, bool $public = false): void
    {
        $this->add('PUT', $path, $handler, $public);
    }

    private function add(string $method, string $path, callable $handler, bool $public): void
    {
        $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $path);
        $keys = [];
        if (preg_match_all('#\{([^/]+)\}#', $path, $matches)) {
            $keys = $matches[1];
        }
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'pattern' => '#^' . $pattern . '$#',
            'keys' => $keys,
            'handler' => $handler,
            'public' => $public,
        ];
    }

    public function dispatch(Request $request, AuthService $authService): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }
            array_shift($matches);
            $params = [];
            foreach ($route['keys'] as $index => $key) {
                $params[$key] = $matches[$index] ?? null;
            }
            $currentUser = null;
            if (!$route['public']) {
                $currentUser = $authService->authenticate($request);
                if ($currentUser === null) {
                    return Response::error(401, '未登录');
                }
            }
            return call_user_func($route['handler'], $request, $params, $currentUser);
        }

        return Response::error(404, '未找到接口');
    }
}
