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

    public function get(string $path, mixed $action): Route
    {
        return $this->add(['GET', 'HEAD'], $path, $action);
    }

    public function post(string $path, mixed $action): Route
    {
        return $this->add(['POST'], $path, $action);
    }

    public function put(string $path, mixed $action): Route
    {
        return $this->add(['PUT'], $path, $action);
    }

    public function patch(string $path, mixed $action): Route
    {
        return $this->add(['PATCH'], $path, $action);
    }

    public function delete(string $path, mixed $action): Route
    {
        return $this->add(['DELETE'], $path, $action);
    }

    public function options(string $path, mixed $action): Route
    {
        return $this->add(['OPTIONS'], $path, $action);
    }

    public function any(string $path, mixed $action): Route
    {
        return $this->add(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $path, $action);
    }

    public function match(array $methods, string $path, mixed $action): Route
    {
        return $this->add(array_map('strtoupper', $methods), $path, $action);
    }

    public function resource(string $name, mixed $controller): void
    {
        $parameter = str_contains($name, '/') ? basename($name) : $name;

        $this->get($name, [$controller, 'index'])->name($name . '.index');
        $this->get($name . '/create', [$controller, 'create'])->name($name . '.create');
        $this->post($name, [$controller, 'store'])->name($name . '.store');
        $this->get($name . '/{' . $parameter . '}', [$controller, 'show'])->name($name . '.show');
        $this->get($name . '/{' . $parameter . '}/edit', [$controller, 'edit'])->name($name . '.edit');
        $this->match(['PUT', 'PATCH'], $name . '/{' . $parameter . '}', [$controller, 'update'])->name($name . '.update');
        $this->delete($name . '/{' . $parameter . '}', [$controller, 'destroy'])->name($name . '.destroy');
    }

    public function apiResource(string $name, mixed $controller): void
    {
        $parameter = str_contains($name, '/') ? basename($name) : $name;

        $this->get($name, [$controller, 'index'])->name($name . '.index');
        $this->post($name, [$controller, 'store'])->name($name . '.store');
        $this->get($name . '/{' . $parameter . '}', [$controller, 'show'])->name($name . '.show');
        $this->match(['PUT', 'PATCH'], $name . '/{' . $parameter . '}', [$controller, 'update'])->name($name . '.update');
        $this->delete($name . '/{' . $parameter . '}', [$controller, 'destroy'])->name($name . '.destroy');
    }

    public function fallback(mixed $action): Route
    {
        return $this->add(['GET'], '{fallbackPlaceholder}', $action)->where('fallbackPlaceholder', '.*');
    }

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
        $current = end($this->groupStack) ?: [
            'prefix' => '',
            'name' => '',
            'middleware' => []
        ];

        $prefix = isset($attributes['prefix'])
            ? $current['prefix'] . '/' . trim($attributes['prefix'], '/')
            : $current['prefix'];

        $name = isset($attributes['name'])
            ? $current['name'] . $attributes['name']
            : $current['name'];

        $middleware = array_merge($current['middleware'], (array) ($attributes['middleware'] ?? []));

        $this->groupStack[] = [
            'prefix' => $prefix,
            'name' => $name,
            'middleware' => $middleware
        ];
    }

    private function popGroup(): void
    {
        array_pop($this->groupStack);
    }

    private function add(array $methods, string $path, mixed $action): Route
    {
        $attributes = $this->pendingAttributes;
        $this->pendingAttributes = [];

        if (!empty($attributes)) {
            $this->pushGroup($attributes);
        }

        $current = end($this->groupStack) ?: [
            'prefix' => '',
            'name' => '',
            'middleware' => []
        ];

        $fullPath = $current['prefix'] . '/' . ltrim($path, '/');
        $fullPath = $fullPath === '/' ? '/' : rtrim($fullPath, '/');

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
