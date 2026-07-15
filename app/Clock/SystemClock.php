<?php

declare(strict_types=1);

namespace App\Clock;

use Infrasonic\Http\Attribute\Service;

#[Service]
final class SystemClock implements Clock
{
    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now');
    }
}
