<?php

declare(strict_types=1);

namespace Avik\Path;

final class RouteCollection
{
    /** @var Route[] */
    private array $routes = [];

    /** @var array<string, Route> */
    private array $namedRoutes = [];

    public function add(Route $route): void
    {
        $this->routes[] = $route;

        if ($route->name !== null) {
            $this->namedRoutes[$route->name] = $route;
        }
    }

    public function all(): array
    {
        return $this->routes;
    }

    public function getByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function hasNamedRoute(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }
}