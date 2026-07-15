<?php

declare(strict_types=1);

namespace Infrasonic\Runtime\Exception;

/**
 * Thrown when a route parameter cannot be cast to its declared type.
 * Treated as a 400 Bad Request by the kernel.
 */
final class BadRouteParameter extends \RuntimeException
{
    public static function create(string $name, string $value, string $type): self
    {
        return new self(\sprintf('Route parameter "%s" with value "%s" is not a valid %s.', $name, $value, $type));
    }
}
