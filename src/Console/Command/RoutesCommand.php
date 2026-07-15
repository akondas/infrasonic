<?php

declare(strict_types=1);

namespace Infrasonic\Console\Command;

use Infrasonic\Compiler\ClassFinder;
use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Compiler\SourceScanner;
use Infrasonic\Console\Command;
use Infrasonic\Console\Output;
use Infrasonic\Console\ProjectConfig;

/**
 * Lists every route discovered in the application source.
 */
final class RoutesCommand implements Command
{
    public function __construct(
        private readonly string $root,
        private readonly ClassFinder $finder = new ClassFinder(),
        private readonly SourceScanner $scanner = new SourceScanner(),
    ) {
    }

    public function name(): string
    {
        return 'routes';
    }

    public function description(): string
    {
        return 'List all routes declared in the application.';
    }

    public function run(array $args, Output $output): int
    {
        $project = ProjectConfig::load($this->root);

        $output->title('Infrasonic · routes');

        try {
            $scan = $this->scanner->scan($this->finder->find($project->compiler->scanPaths));
        } catch (CompilerError $e) {
            $output->error($e->getMessage());

            return 1;
        }

        if ([] === $scan->routes) {
            $output->info('No routes declared.');

            return 0;
        }

        $routes = $scan->routes;
        usort($routes, static fn ($a, $b) => [$a->path, $a->httpMethod] <=> [$b->path, $b->httpMethod]);

        $methodWidth = 0;
        $pathWidth = 0;
        foreach ($routes as $route) {
            $methodWidth = max($methodWidth, \strlen($route->httpMethod));
            $pathWidth = max($pathWidth, \strlen($route->path));
        }

        foreach ($routes as $route) {
            $output->writeln(\sprintf(
                '  %s  %s  → %s::%s',
                str_pad($route->httpMethod, $methodWidth),
                str_pad($route->path, $pathWidth),
                $route->controller,
                $route->action,
            ));
        }

        return 0;
    }
}
