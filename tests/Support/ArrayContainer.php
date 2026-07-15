<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Support;

use Infrasonic\Runtime\Container;
use Infrasonic\Runtime\Exception\ServiceNotFound;

/**
 * A trivial map-backed container for exercising the runtime in isolation.
 */
final class ArrayContainer implements Container
{
    /**
     * @param array<class-string, object> $services
     */
    public function __construct(private readonly array $services)
    {
    }

    public function get(string $id): object
    {
        return $this->services[$id] ?? throw ServiceNotFound::forId($id);
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
