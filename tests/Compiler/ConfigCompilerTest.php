<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Compiler;

use Infrasonic\Compiler\ConfigCompiler;
use Infrasonic\Compiler\Exception\CompilerError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigCompiler::class)]
final class ConfigCompilerTest extends TestCase
{
    public function testFreezesScalarParametersIntoReturnableArray(): void
    {
        $code = (new ConfigCompiler())->compile([
            'app.name' => 'Infrasonic',
            'app.port' => 8080,
            'app.debug' => true,
            'app.nullable' => null,
        ]);

        $file = tempnam(sys_get_temp_dir(), 'infra_cfg_').'.php';
        file_put_contents($file, $code);

        $frozen = require $file;
        unlink($file);

        self::assertSame([
            'app.name' => 'Infrasonic',
            'app.port' => 8080,
            'app.debug' => true,
            'app.nullable' => null,
        ], $frozen);
    }

    public function testRejectsNonScalarParameters(): void
    {
        $this->expectException(CompilerError::class);
        $this->expectExceptionMessageMatches('/must be scalar or null/');

        (new ConfigCompiler())->compile(['bad' => new \stdClass()]);
    }
}
