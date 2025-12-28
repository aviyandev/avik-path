<?php

declare(strict_types=1);

namespace Avik\Path\Exceptions;

final class MethodNotAllowedException extends \RuntimeException
{
    public function __construct(array $methods)
    {
        parent::__construct('Method not allowed. Allowed: ' . implode(', ', $methods), 405);
    }
}
