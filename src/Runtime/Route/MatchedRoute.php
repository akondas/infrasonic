<?php

declare(strict_types=1);

namespace Infrasonic\Runtime\Route;

/**
 * The result of matching a request against the compiled route table.
 *
 * @phpstan-type ArgDescriptor array{source: 'route', name: string, cast: string}|array{source: 'request'}
 */
final class MatchedRoute
{
    /**
     * @param class-string          $controller
     * @param list<mixed>           $args       ordered argument descriptors for the action
     * @param array<string, string> $params     raw captured route parameters, keyed by name
     *
     * @phpstan-param list<ArgDescriptor> $args
     */
    public function __construct(
        public readonly string $controller,
        public readonly string $action,
        public readonly array $args,
        public readonly array $params,
    ) {
    }
}
