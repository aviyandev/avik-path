<?php

declare(strict_types=1);

namespace Avik\Path;

use Avik\Path\Exceptions\RouteNotFoundException;
use Avik\Path\Exceptions\MethodNotAllowedException;

final class Dispatcher
{
    public function dispatch(string $method, string $uri, RouteCollection $routes): array
    {
        $allowed = [];

        foreach ($routes->all() as $route) {
            if ($route->matches($method, $uri)) {
                return [$route, $route->parameters($uri)];
            }

            if ($route->path === $uri) {
                $allowed[] = $route->method;
            }
        }

        if ($allowed) {
            throw new MethodNotAllowedException($allowed);
        }

        throw new RouteNotFoundException();
    }
}
