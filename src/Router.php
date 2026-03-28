<?php

declare(strict_types=1);

namespace Avik\Path;

final class Router
{
    private RouteCollection $routes;
    private array $groupStack = [];
    private array $pendingAttributes = [];

    public function __construct(?RouteCollection $routes = null)
    {
        $this->routes = $routes ?? new RouteCollection();
    }

    /* =========================
       HTTP VERBS
       ========================= */

    public function get(string $path, mixed $action): Route
    {
        return $this->addRoute(['GET', 'HEAD'], $path, $action);
    }

    public function post(string $path, mixed $action): Route
    {
        return $this->addRoute(['POST'], $path, $action);
    }

    public function put(string $path, mixed $action): Route
    {
        return $this->addRoute(['PUT'], $path, $action);
    }

    public function patch(string $path, mixed $action): Route
    {
        return $this->addRoute(['PATCH'], $path, $action);
    }

    public function delete(string $path, mixed $action): Route
    {
        return $this->addRoute(['DELETE'], $path, $action);
    }

    public function options(string $path, mixed $action): Route
    {
        return $this->addRoute(['OPTIONS'], $path, $action);
    }

    public function any(string $path, mixed $action): Route
    {
        return $this->addRoute(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $path, $action);
    }

    public function match(array $methods, string $path, mixed $action): Route
    {
        return $this->addRoute(array_map('strtoupper', $methods), $path, $action);
    }

    /* =========================
       RESOURCE ROUTES
       ========================= */

    public function resource(string $name, mixed $controller): void
    {
        $param = str_contains($name, '/') ? basename($name) : $name;

        $this->get($name, [$controller, 'index'])->name($name . '.index');
        $this->get($name . '/create', [$controller, 'create'])->name($name . '.create');
        $this->post($name, [$controller, 'store'])->name($name . '.store');
        $this->get($name . '/{' . $param . '}', [$controller, 'show'])->name($name . '.show');
        $this->get($name . '/{' . $param . '}/edit', [$controller, 'edit'])->name($name . '.edit');
        $this->match(['PUT', 'PATCH'], $name . '/{' . $param . '}', [$controller, 'update'])->name($name . '.update');
        $this->delete($name . '/{' . $param . '}', [$controller, 'destroy'])->name($name . '.destroy');
    }

    public function apiResource(string $name, mixed $controller): void
    {
        $param = str_contains($name, '/') ? basename($name) : $name;

        $this->get($name, [$controller, 'index'])->name($name . '.index');
        $this->post($name, [$controller, 'store'])->name($name . '.store');
        $this->get($name . '/{' . $param . '}', [$controller, 'show'])->name($name . '.show');
        $this->match(['PUT', 'PATCH'], $name . '/{' . $param . '}', [$controller, 'update'])->name($name . '.update');
        $this->delete($name . '/{' . $param . '}', [$controller, 'destroy'])->name($name . '.destroy');
    }

    public function fallback(mixed $action): Route
    {
        return $this->any('{fallbackPlaceholder}', $action)
            ->where('fallbackPlaceholder', '.*')
            ->name('fallback');
    }

    /* =========================
       ROUTE GROUPING
       ========================= */

    public function prefix(string $prefix): self
    {
        $this->pendingAttributes['prefix'] = $prefix;
        return $this;
    }

    public function name(string $name): self
    {
        $this->pendingAttributes['name'] = $name;
        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        $this->pendingAttributes['middleware'] = (array) $middleware;
        return $this;
    }

    public function group(\Closure $callback): void
    {
        $attributes = $this->pendingAttributes;
        $this->pendingAttributes = [];

        $this->pushGroup($attributes);
        $callback($this);
        $this->popGroup();
    }

    private function pushGroup(array $attributes): void
    {
        $parent = end($this->groupStack) ?: ['prefix' => '', 'name' => '', 'middleware' => []];

        $prefix = trim(($parent['prefix'] ?? '') . '/' . ($attributes['prefix'] ?? ''), '/');
        $prefix = $prefix === '' ? '' : '/' . $prefix;

        $name = ($parent['name'] ?? '') . ($attributes['name'] ?? '');

        $middleware = array_merge(
            $parent['middleware'] ?? [],
            $attributes['middleware'] ?? []
        );

        $this->groupStack[] = [
            'prefix'     => $prefix,
            'name'       => $name,
            'middleware' => $middleware
        ];
    }

    private function popGroup(): void
    {
        array_pop($this->groupStack);
    }

    /* =========================
       INTERNAL ROUTE CREATION
       ========================= */

    private function addRoute(array $methods, string $path, mixed $action): Route
    {
        $attributes = $this->pendingAttributes;
        $this->pendingAttributes = [];

        if (!empty($attributes)) {
            $this->pushGroup($attributes);
        }

        $current = end($this->groupStack) ?: ['prefix' => '', 'name' => '', 'middleware' => []];

        $fullPath = rtrim($current['prefix'] . '/' . ltrim($path, '/'), '/');
        $fullPath = $fullPath === '' ? '/' : $fullPath;

        $route = new Route(
            $methods,
            $fullPath,
            $action,
            $current['name'] ?: null,
            $current['middleware']
        );

        if (!empty($attributes)) {
            $this->popGroup();
        }

        $this->routes->add($route);
        return $route;
    }

    public function routes(): RouteCollection
    {
        return $this->routes;
    }
}