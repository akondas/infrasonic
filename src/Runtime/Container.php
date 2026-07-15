<?php

declare(strict_types=1);

namespace Infrasonic\Runtime;

/**
 * Minimal service locator contract. The compiled container is the only
 * implementation used in production; it performs no reflection.
 */
interface Container
{
    /**
     * @param class-string $id
     */
    public function get(string $id): object;

    /**
     * @param class-string $id
     */
    public function has(string $id): bool;
}
