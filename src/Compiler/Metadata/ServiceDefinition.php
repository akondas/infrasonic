<?php

declare(strict_types=1);

namespace Infrasonic\Compiler\Metadata;

/**
 * A discovered service (or controller) and its constructor dependencies.
 */
final class ServiceDefinition
{
    /**
     * @param class-string     $id           the concrete class name
     * @param list<Dependency> $dependencies constructor dependencies, in order
     */
    public function __construct(
        public readonly string $id,
        public readonly array $dependencies,
        public readonly bool $isController,
    ) {
    }
}
