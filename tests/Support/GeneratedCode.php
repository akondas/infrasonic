<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Support;

/**
 * Evaluates generated compiler output under a unique namespace so multiple
 * fixtures can be loaded within one test process without class collisions.
 */
trait GeneratedCode
{
    protected function instantiateGenerated(string $code, string $shortClassName): object
    {
        $namespace = 'Infrasonic\\Generated\\T'.bin2hex(random_bytes(6));

        $code = str_replace('namespace Infrasonic\\Generated;', 'namespace '.$namespace.';', $code);
        $code = preg_replace('/^<\?php/', '', $code, 1) ?? $code;
        $code = str_replace('declare(strict_types=1);', '', $code);

        eval($code);

        /** @var class-string $fqcn */
        $fqcn = $namespace.'\\'.$shortClassName;

        return new $fqcn();
    }
}
