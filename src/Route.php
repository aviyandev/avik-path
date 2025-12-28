<?php

declare(strict_types=1);

namespace Avik\Path;

final class Route
{
    private array $wheres = [];

    public function __construct(
        public array $methods,
        public string $path,
        public mixed $action,
        public ?string $name = null,
        public array $middleware = []
    ) {
        $this->path = '/' . ltrim($path, '/');
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);
        return $this;
    }

    public function where(string|array $name, ?string $expression = null): self
    {
        if (is_array($name)) {
            $this->wheres = array_merge($this->wheres, $name);
        } else {
            $this->wheres[$name] = $expression;
        }
        return $this;
    }

    public function whereNumber(string $parameter): self
    {
        return $this->where($parameter, '[0-9]+');
    }

    public function whereAlpha(string $parameter): self
    {
        return $this->where($parameter, '[a-zA-Z]+');
    }

    public function whereAlphaNumeric(string $parameter): self
    {
        return $this->where($parameter, '[a-zA-Z0-9]+');
    }

    public function matches(string $method, string $uri): bool
    {
        if (!in_array(strtoupper($method), $this->methods)) {
            return false;
        }

        return $this->matchesPath($uri);
    }

    public function matchesPath(string $uri): bool
    {
        $pattern = $this->compilePattern();
        return (bool) preg_match('#^' . $pattern . '$#', $uri);
    }

    public function parameters(string $uri): array
    {
        preg_match_all('#\{([^/]+)\}#', $this->path, $keys);
        $pattern = $this->compilePattern();

        if (preg_match('#^' . $pattern . '$#', $uri, $values)) {
            array_shift($values);
            return array_combine($keys[1], $values) ?: [];
        }

        return [];
    }

    private function compilePattern(): string
    {
        return preg_replace_callback('#\{([^/]+)\}#', function ($matches) {
            $parameter = $matches[1];
            return '(' . ($this->wheres[$parameter] ?? '[^/]+') . ')';
        }, $this->path);
    }
}
