<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Fixtures\Scan;

use Infrasonic\Http\Attribute\Service;

#[Service]
final class BuiltinDepService
{
    public function __construct(private readonly string $name)
    {
    }

    public function name(): string
    {
        return $this->name;
    }
}
