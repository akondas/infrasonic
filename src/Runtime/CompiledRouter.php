<?php

declare(strict_types=1);

namespace Infrasonic\Runtime;

use Infrasonic\Runtime\Route\MatchedRoute;

/**
 * Base class for the generated router.
 *
 * Static routes match with a single hash lookup. Dynamic routes are matched
 * against pre-compiled regular expressions, grouped by method so only the
 * candidates for the request's method are ever tested. The route tables are
 * produced at build time and cached for the lifetime of the worker.
 *
 * @phpstan-type RouteEntry array{controller: class-string, action: string, args: list<mixed>}
 * @phpstan-type DynamicEntry array{regex: string, controller: class-string, action: string, args: list<mixed>}
 */
abstract class CompiledRouter
{
    /** @var array<string, array<string, array<string, mixed>>>|null */
    private ?array $static = null;

    /** @var array<string, list<array<string, mixed>>>|null */
    private ?array $dynamic = null;

    /**
     * @return array<string, array<string, array<string, mixed>>>
     *
     * @phpstan-return array<string, array<string, RouteEntry>>
     */
    abstract protected function staticRoutes(): array;

    /**
     * @return array<string, list<array<string, mixed>>>
     *
     * @phpstan-return array<string, list<DynamicEntry>>
     */
    abstract protected function dynamicRoutes(): array;

    public function match(string $method, string $path): ?MatchedRoute
    {
        $this->static ??= $this->staticRoutes();
        $this->dynamic ??= $this->dynamicRoutes();

        $entry = $this->static[$method][$path] ?? null;
        if (null !== $entry) {
            /* @var RouteEntry $entry */
            return new MatchedRoute($entry['controller'], $entry['action'], $entry['args'], []);
        }

        foreach ($this->dynamic[$method] ?? [] as $candidate) {
            /** @var DynamicEntry $candidate */
            if (1 === preg_match($candidate['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (\is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                return new MatchedRoute($candidate['controller'], $candidate['action'], $candidate['args'], $params);
            }
        }

        return null;
    }

    /**
     * True when the path exists for a different method (used to answer 405 vs 404).
     */
    public function pathExists(string $path): bool
    {
        $this->static ??= $this->staticRoutes();
        $this->dynamic ??= $this->dynamicRoutes();

        foreach ($this->static as $paths) {
            if (isset($paths[$path])) {
                return true;
            }
        }

        foreach ($this->dynamic as $candidates) {
            foreach ($candidates as $candidate) {
                /** @var DynamicEntry $candidate */
                if (1 === preg_match($candidate['regex'], $path)) {
                    return true;
                }
            }
        }

        return false;
    }
}
