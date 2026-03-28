<?php

declare(strict_types=1);

namespace Avik\Path;

use Avik\Path\Exceptions\RouteNotFoundException;
use Avik\Path\Exceptions\MethodNotAllowedException;

final class Dispatcher
{
    public function dispatch(string $method, string $uri, RouteCollection $routes): array
    {
        $uri = '/' . trim($uri, '/');
        $allowedMethods = [];

        foreach ($routes->all() as $route) {
            if ($route->matches($method, $uri)) {
                return [$route, $route->parameters($uri)];
            }

            if ($route->matchesPath($uri)) {
                $allowedMethods = array_merge($allowedMethods, $route->methods);
            }
        }

        if (!empty($allowedMethods)) {
            throw new MethodNotAllowedException(array_unique($allowedMethods));
        }

        throw new RouteNotFoundException();
    }
}