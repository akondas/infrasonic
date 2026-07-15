<?php

declare(strict_types=1);

namespace Infrasonic\Compiler;

use Infrasonic\Compiler\Exception\CompilerError;

/**
 * Freezes configuration parameters into a plain PHP file that simply returns an
 * array. OPcache keeps the array resident, so reading config at runtime costs
 * nothing beyond an array lookup.
 */
final class ConfigCompiler
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function compile(array $parameters): string
    {
        $frozen = [];
        foreach ($parameters as $key => $value) {
            if (null !== $value && !\is_scalar($value)) {
                throw new CompilerError(\sprintf('Config parameter "%s" must be scalar or null, got %s.', $key, get_debug_type($value)));
            }
            $frozen[$key] = $value;
        }

        $exported = var_export($frozen, true);

        return <<<PHP
            <?php

            declare(strict_types=1);

            /*
             * Compiled by Infrasonic. Do not edit.
             */
            return {$exported};

            PHP;
    }
}
