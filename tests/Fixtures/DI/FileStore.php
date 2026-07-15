<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Fixtures\DI;

final class FileStore implements Store
{
    public function name(): string
    {
        return 'file';
    }
}
