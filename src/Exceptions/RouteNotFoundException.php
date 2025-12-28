<?php

declare(strict_types=1);

namespace Avik\Path\Exceptions;

final class RouteNotFoundException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Route not found', 404);
    }
}
