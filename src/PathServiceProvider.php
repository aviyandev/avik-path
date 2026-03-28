<?php

declare(strict_types=1);

namespace Avik\Path;

use Avik\Ignite\Application;
use Avik\Seed\Contracts\ServiceProvider;

final class PathServiceProvider implements ServiceProvider
{
    public function __construct(private Application $app) {}

    public function register(): void
    {
        $this->app->singleton(RouteCollection::class);
        $this->app->singleton(Router::class, fn($app) => new Router($app->make(RouteCollection::class)));
        $this->app->singleton(Dispatcher::class);
    }

    public function boot(): void
    {
        // Future: register route macros, etc.
    }
}