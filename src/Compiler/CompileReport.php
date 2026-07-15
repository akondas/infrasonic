<?php

declare(strict_types=1);

namespace Infrasonic\Compiler;

/**
 * Summary of a compilation run, for CLI output.
 */
final class CompileReport
{
    /**
     * @param list<string> $files absolute paths written
     */
    public function __construct(
        public readonly int $services,
        public readonly int $controllers,
        public readonly int $routes,
        public readonly array $files,
    ) {
    }
}
