<?php

declare(strict_types=1);

namespace Avik\Path;

final class Router
{
    private RouteCollection $routes;
    private array $groupStack = [];

    public function __construct(?RouteCollection $routes = null)
    {
        $this->routes = $routes ?? new RouteCollection();
    }

    public function get(string $path, mixed $action): self
    {
        return $this->add('GET', $path, $action);
    }

    public function post(string $path, mixed $action): self
    {
        return $this->add('POST', $path, $action);
    }

    public function name(string $name): self
    {
        $this->groupStack['name'] = $name;
        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        $this->groupStack['middleware'] = (array) $middleware;
        return $this;
    }

    public function group(\Closure $callback): void
    {
        $previous = $this->groupStack;
        $callback($this);
        $this->groupStack = $previous;
    }

    private function add(string $method, string $path, mixed $action): self
    {
        $this->routes->add(new Route(
            $method,
            $path,
            $action,
            $this->groupStack['name'] ?? null,
            $this->groupStack['middleware'] ?? []
        ));

        return $this;
    }

    public function routes(): RouteCollection
    {
        return $this->routes;
    }
}
