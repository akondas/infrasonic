<?php

declare(strict_types=1);

namespace Infrasonic\Http\Attribute;

/**
 * Marks a class as a service to be wired into the compiled container.
 *
 * Discovered at build time by the SourceScanner. Controllers (classes with
 * at least one #[Route] method) are registered automatically and do not need
 * this attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Service
{
}
