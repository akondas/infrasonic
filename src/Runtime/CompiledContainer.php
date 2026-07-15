<?php

declare(strict_types=1);

namespace Infrasonic\Runtime;

use Infrasonic\Runtime\Exception\ServiceNotFound;

/**
 * Base class for the generated container.
 *
 * The build step emits a subclass (Infrasonic\Generated\CompiledContainer)
 * whose get()/has() are plain match() expressions and whose factory methods
 * are hand-written-style `new Foo($this->getBar())` calls. There is no
 * reflection and no configuration graph to walk at runtime — only method calls
 * and a singleton cache.
 */
abstract class CompiledContainer implements Container
{
    /**
     * Singleton instances, keyed by service id.
     *
     * @var array<class-string, object>
     */
    protected array $singletons = [];

    /**
     * @param class-string $id
     *
     * @throws ServiceNotFound
     */
    protected function fail(string $id): never
    {
        throw ServiceNotFound::forId($id);
    }
}
