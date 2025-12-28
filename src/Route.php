<?php

declare(strict_types=1);

namespace Avik\Path;

final class Route
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly mixed $action,
        public readonly ?string $name = null,
        public readonly array $middleware = []
    ) {}

    public function matches(string $method, string $uri): bool
    {
        if ($this->method !== $method) {
            return false;
        }

        $pattern = preg_replace('#\{[^/]+\}#', '([^/]+)', $this->path);
        return (bool) preg_match('#^' . $pattern . '$#', $uri);
    }

    public function parameters(string $uri): array
    {
        preg_match_all('#\{([^/]+)\}#', $this->path, $keys);
        preg_match('#^' . preg_replace('#\{[^/]+\}#', '([^/]+)', $this->path) . '$#', $uri, $values);

        array_shift($values);
        return array_combine($keys[1], $values) ?: [];
    }
}
