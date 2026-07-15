<?php

declare(strict_types=1);

namespace Infrasonic\Compiler\Metadata;

/**
 * A single constructor dependency of a service, as declared in source.
 * The declared type may be an interface; it is resolved to a concrete service
 * by the ContainerCompiler.
 */
final class Dependency
{
    /**
     * @param class-string $type          the declared parameter type (interface or class)
     * @param string       $parameterName the constructor parameter name (for error messages)
     */
    public function __construct(
        public readonly string $type,
        public readonly string $parameterName,
    ) {
    }
}
