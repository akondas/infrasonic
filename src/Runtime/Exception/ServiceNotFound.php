<?php

declare(strict_types=1);

namespace Infrasonic\Runtime\Exception;

final class ServiceNotFound extends \RuntimeException
{
    /**
     * @param class-string $id
     */
    public static function forId(string $id): self
    {
        return new self(\sprintf('Service "%s" is not registered in the compiled container.', $id));
    }
}
