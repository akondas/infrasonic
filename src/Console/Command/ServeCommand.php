<?php

declare(strict_types=1);

namespace Infrasonic\Console\Command;

use Infrasonic\Compiler\Compiler;
use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Console\Command;
use Infrasonic\Console\Output;
use Infrasonic\Console\ProjectConfig;

/**
 * Builds the app and starts a development server.
 *
 * Uses PHP's built-in server for local development (boots per request). For
 * production, run the compiled app under FrankenPHP worker mode with
 * public/worker.php — see the README and Dockerfile.
 */
final class ServeCommand implements Command
{
    public function __construct(
        private readonly string $root,
        private readonly Compiler $compiler = new Compiler(),
    ) {
    }

    public function name(): string
    {
        return 'serve';
    }

    public function description(): string
    {
        return 'Build and start a development server (use FrankenPHP in production).';
    }

    public function run(array $args, Output $output): int
    {
        $project = ProjectConfig::load($this->root);
        $host = $this->option($args, 'host') ?? $project->host;
        $port = (int) ($this->option($args, 'port') ?? (string) $project->port);

        $output->title('Infrasonic · serve');

        try {
            $report = $this->compiler->compile($project->compiler);
        } catch (CompilerError $e) {
            $output->error($e->getMessage());

            return 1;
        }

        $output->success(\sprintf('Compiled %d route(s).', $report->routes));

        $docroot = $this->root.'/public';
        if (!is_file($docroot.'/index.php')) {
            $output->error('Missing public/index.php entry point.');

            return 1;
        }

        $output->info(\sprintf('Development server: http://%s:%d', $host, $port));
        $output->comment('Press Ctrl+C to stop. For production use FrankenPHP (see README).');

        $command = \sprintf(
            '%s -S %s:%d -t %s %s',
            escapeshellarg(\PHP_BINARY),
            $host,
            $port,
            escapeshellarg($docroot),
            escapeshellarg($docroot.'/index.php'),
        );

        passthru($command, $exitCode);

        return $exitCode;
    }

    /**
     * @param list<string> $args
     */
    private function option(array $args, string $name): ?string
    {
        $prefix = '--'.$name.'=';
        foreach ($args as $i => $arg) {
            if (str_starts_with($arg, $prefix)) {
                return substr($arg, \strlen($prefix));
            }
            if ($arg === '--'.$name && isset($args[$i + 1])) {
                return $args[$i + 1];
            }
        }

        return null;
    }
}
