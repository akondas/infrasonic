<?php

declare(strict_types=1);

namespace Infrasonic\Compiler\Metadata;

/**
 * Describes how one action argument is supplied at runtime: either the current
 * request object, or a route parameter cast to a scalar type.
 */
final class RouteArgument
{
    private function __construct(
        public readonly string $source,
        public readonly ?string $name,
        public readonly ?string $cast,
    ) {
    }

    public static function request(): self
    {
        return new self('request', null, null);
    }

    public static function routeParam(string $name, string $cast): self
    {
        return new self('route', $name, $cast);
    }

    /**
     * @return array{source: 'request'}|array{source: 'route', name: string, cast: string}
     */
    public function toArray(): array
    {
        if ('request' === $this->source) {
            return ['source' => 'request'];
        }

        \assert(null !== $this->name && null !== $this->cast);

        return ['source' => 'route', 'name' => $this->name, 'cast' => $this->cast];
    }
}
