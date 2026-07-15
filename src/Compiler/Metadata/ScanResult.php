<?php

declare(strict_types=1);

namespace Infrasonic\Compiler\Metadata;

/**
 * The full set of metadata discovered by scanning the source tree.
 */
final class ScanResult
{
    /**
     * @param list<ServiceDefinition> $services
     * @param list<RouteDefinition>   $routes
     */
    public function __construct(
        public readonly array $services,
        public readonly array $routes,
    ) {
    }
}
