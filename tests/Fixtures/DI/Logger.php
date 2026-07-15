<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Fixtures\DI;

final class Logger
{
    /** @var list<string> */
    public array $lines = [];

    public function log(string $line): void
    {
        $this->lines[] = $line;
    }
}
