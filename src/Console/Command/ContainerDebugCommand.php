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
 * Prints the discovered services and their constructor dependencies.
 */
final class ContainerDebugCommand implements Command
{
    public function __construct(
        private readonly string $root,
        private readonly ClassFinder $finder = new ClassFinder(),
        private readonly SourceScanner $scanner = new SourceScanner(),
    ) {
    }

    public function name(): string
    {
        return 'container:debug';
    }

    public function description(): string
    {
        return 'Show discovered services and their dependencies.';
    }

    public function run(array $args, Output $output): int
    {
        $project = ProjectConfig::load($this->root);

        $output->title('Infrasonic · container');

        try {
            $scan = $this->scanner->scan($this->finder->find($project->compiler->scanPaths));
        } catch (CompilerError $e) {
            $output->error($e->getMessage());

            return 1;
        }

        if ([] === $scan->services) {
            $output->info('No services discovered.');

            return 0;
        }

        foreach ($scan->services as $service) {
            $tag = $service->isController ? ' (controller)' : '';
            $output->writeln('  '.$service->id.$tag);
            foreach ($service->dependencies as $dependency) {
                $output->comment('      ├─ $'.$dependency->parameterName.': '.$dependency->type);
            }
        }

        return 0;
    }
}
