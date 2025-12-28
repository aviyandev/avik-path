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
        $uri = '/' . trim($uri, '/');

        foreach ($routes->all() as $route) {
            if ($route->matches($method, $uri)) {
                return [$route, $route->parameters($uri)];
            }

            // Check if path matches any other method for 405
            if ($route->matchesPath($uri)) {
                $allowed = array_merge($allowed, $route->methods);
            }
        }

        if ($allowed) {
            throw new MethodNotAllowedException(array_unique($allowed));
        }

        throw new RouteNotFoundException();
    }
}
