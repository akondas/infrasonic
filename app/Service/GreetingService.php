<?php

declare(strict_types=1);

namespace App\Service;

use App\Clock\Clock;
use Infrasonic\Http\Attribute\Service;

#[Service]
final class GreetingService
{
    public function __construct(private readonly Clock $clock)
    {
    }

    public function greet(string $name): string
    {
        return \sprintf('Hello %s, it is %s.', $name, $this->clock->now()->format('H:i:s'));
    }
}
