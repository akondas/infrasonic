<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Fixtures\DI;

final class Mailer
{
    public function __construct(public readonly Logger $logger)
    {
    }
}
