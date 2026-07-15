<?php

declare(strict_types=1);

namespace Infrasonic\Console\Command;

use Infrasonic\Compiler\Compiler;
use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Console\Command;
use Infrasonic\Console\Output;
use Infrasonic\Console\ProjectConfig;

/**
 * Compiles the application into runtime artifacts.
 */
final class BuildCommand implements Command
{
    public function __construct(
        private readonly string $root,
        private readonly Compiler $compiler = new Compiler(),
    ) {
    }

    public function name(): string
    {
        return 'build';
    }

    public function description(): string
    {
        return 'Compile the application (container, router, config, bootstrap).';
    }

    public function run(array $args, Output $output): int
    {
        $project = ProjectConfig::load($this->root);

        $output->title('Infrasonic · build');

        try {
            $report = $this->compiler->compile($project->compiler);
        } catch (CompilerError $e) {
            $output->error($e->getMessage());

            return 1;
        }

        $output->success(\sprintf(
            'Compiled %d service(s), %d controller(s), %d route(s).',
            $report->services,
            $report->controllers,
            $report->routes,
        ));

        foreach ($report->files as $file) {
            $output->comment('  '.$this->relative($file));
        }

        return 0;
    }

    private function relative(string $path): string
    {
        $prefix = $this->root.\DIRECTORY_SEPARATOR;

        return str_starts_with($path, $prefix) ? substr($path, \strlen($prefix)) : $path;
    }
}
