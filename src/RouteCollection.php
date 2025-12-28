<?php

declare(strict_types=1);

namespace Avik\Path;

final class RouteCollection
{
    private array $routes = [];

    public function add(Route $route): void
    {
        $this->routes[] = $route;
    }

    public function all(): array
    {
        return $this->routes;
    }
}
