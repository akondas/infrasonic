<?php

declare(strict_types=1);

namespace Infrasonic\Compiler;

/**
 * Inputs for a compilation run.
 */
final class CompilerConfig
{
    /**
     * @param string                            $outputDir  absolute path for generated artifacts
     * @param list<string>                      $scanPaths  absolute directories to scan
     * @param array<class-string, class-string> $bindings   interface => concrete
     * @param list<class-string>                $middleware ordered, outermost first
     * @param array<string, mixed>              $parameters config parameters (dot keys)
     */
    public function __construct(
        public readonly string $outputDir,
        public readonly array $scanPaths,
        public readonly array $bindings = [],
        public readonly array $middleware = [],
        public readonly array $parameters = [],
    ) {
    }
}
