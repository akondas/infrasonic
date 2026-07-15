<?php

declare(strict_types=1);

namespace Infrasonic\Console;

/**
 * A console command.
 */
interface Command
{
    public function name(): string;

    public function description(): string;

    /**
     * @param list<string> $args arguments after the command name
     *
     * @return int exit code (0 = success)
     */
    public function run(array $args, Output $output): int;
}
