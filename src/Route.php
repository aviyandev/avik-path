<?php

declare(strict_types=1);

namespace Avik\Path;

final class Route
{
    private array $wheres = [];

    public function __construct(
        public readonly array $methods,
        public string $path,
        public mixed $action,
        public ?string $name = null,
        public array $middleware = []
    ) {
        $this->path = '/' . ltrim($this->path, '/');
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
        if (!in_array(strtoupper($method), $this->methods, true)) {
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
        $pattern = $this->compilePattern();
        if (!preg_match('#^' . $pattern . '$#', $uri, $matches)) {
            return [];
        }

        preg_match_all('#\{([^}]+)\}#', $this->path, $keys);
        array_shift($matches); // Remove full match

        return array_combine($keys[1] ?? [], $matches) ?: [];
    }

    private function compilePattern(): string
    {
        return preg_replace_callback(
            '#\{([^}]+)\}#',
            function (array $matches): string {
                $param = $matches[1];
                $regex = $this->wheres[$param] ?? '[^/]+';
                return '(' . $regex . ')';
            },
            $this->path
        );
    }
}