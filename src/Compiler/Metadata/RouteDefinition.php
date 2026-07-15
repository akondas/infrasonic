<?php

declare(strict_types=1);

namespace Infrasonic\Compiler\Metadata;

/**
 * A discovered route: an HTTP method + path bound to a controller action,
 * with its resolved argument descriptors.
 */
final class RouteDefinition
{
    /**
     * @param class-string        $controller
     * @param list<RouteArgument> $arguments
     */
    public function __construct(
        public readonly string $controller,
        public readonly string $action,
        public readonly string $httpMethod,
        public readonly string $path,
        public readonly array $arguments,
    ) {
    }

    public function isStatic(): bool
    {
        return !str_contains($this->path, '{');
    }
}
