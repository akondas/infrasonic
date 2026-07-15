<?php

declare(strict_types=1);

namespace Infrasonic\Console;

use Infrasonic\Compiler\CompilerConfig;

/**
 * Loads a project's configuration from its config/ directory and turns it into
 * a CompilerConfig plus serving defaults.
 *
 * config/services.php returns: ['bindings' => [...], 'middleware' => [...], 'scan' => [...]]
 * config/parameters.php returns a flat map of dot-keyed scalar parameters.
 */
final class ProjectConfig
{
    private function __construct(
        public readonly CompilerConfig $compiler,
        public readonly string $host,
        public readonly int $port,
    ) {
    }

    public static function load(string $root): self
    {
        $services = self::requireArray($root.'/config/services.php');
        $parameters = self::requireArray($root.'/config/parameters.php');

        /** @var list<string> $scan */
        $scan = self::listOfStrings($services['scan'] ?? ['app']);
        $scanPaths = array_map(static fn (string $dir) => $root.\DIRECTORY_SEPARATOR.$dir, $scan);

        /** @var array<class-string, class-string> $bindings */
        $bindings = \is_array($services['bindings'] ?? null) ? $services['bindings'] : [];
        /** @var list<class-string> $middleware */
        $middleware = self::listOfStrings($services['middleware'] ?? []);

        $host = isset($parameters['app.host']) && \is_string($parameters['app.host']) ? $parameters['app.host'] : '127.0.0.1';
        $port = isset($parameters['app.port']) && is_numeric($parameters['app.port']) ? (int) $parameters['app.port'] : 8080;

        $compiler = new CompilerConfig(
            outputDir: $root.'/var/compiled',
            scanPaths: $scanPaths,
            bindings: $bindings,
            middleware: $middleware,
            parameters: $parameters,
        );

        return new self($compiler, $host, $port);
    }

    /**
     * @return array<string, mixed>
     */
    private static function requireArray(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        $data = require $file;

        return \is_array($data) ? $data : [];
    }

    /**
     * @return list<string>
     */
    private static function listOfStrings(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (\is_string($item)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}
