<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Fixtures\DI;

final class NeedsStore
{
    public function __construct(public readonly Store $store)
    {
    }
}
