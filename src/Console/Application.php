<?php

declare(strict_types=1);

namespace Infrasonic\Console;

use Infrasonic\Console\Command\BuildCommand;
use Infrasonic\Console\Command\ContainerDebugCommand;
use Infrasonic\Console\Command\RoutesCommand;
use Infrasonic\Console\Command\ServeCommand;

/**
 * The `infra` command-line application: registers commands and dispatches argv.
 */
final class Application
{
    public const string VERSION = '0.1.0';

    /** @var array<string, Command> */
    private array $commands = [];

    public function __construct(
        string $root,
        private readonly Output $output = new Output(),
    ) {
        foreach ([
            new BuildCommand($root),
            new ServeCommand($root),
            new RoutesCommand($root),
            new ContainerDebugCommand($root),
        ] as $command) {
            $this->commands[$command->name()] = $command;
        }
    }

    /**
     * @param list<string> $argv the full argv (including script name at index 0)
     */
    public function run(array $argv): int
    {
        $name = $argv[1] ?? 'list';

        if ('list' === $name || 'help' === $name || '--help' === $name || '-h' === $name) {
            $this->listCommands();

            return 0;
        }

        $command = $this->commands[$name] ?? null;
        if (null === $command) {
            $this->output->error(\sprintf('Unknown command "%s".', $name));
            $this->listCommands();

            return 1;
        }

        return $command->run(\array_slice($argv, 2), $this->output);
    }

    private function listCommands(): void
    {
        $this->output->title('Infrasonic '.self::VERSION);
        $this->output->writeln('Usage: infra <command> [options]');
        $this->output->writeln();

        $width = 0;
        foreach ($this->commands as $command) {
            $width = max($width, \strlen($command->name()));
        }

        foreach ($this->commands as $command) {
            $this->output->writeln('  '.str_pad($command->name(), $width).'  '.$command->description());
        }
    }
}
