<?php

declare(strict_types=1);

namespace Infrasonic\Http\Attribute;

use Infrasonic\Http\Method;

/**
 * Declares an HTTP route on a controller method.
 *
 * Resolved entirely at build time by the RouteCompiler; never read at runtime.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class Route
{
    public function __construct(
        public Method $method,
        public string $path,
    ) {
    }
}
