<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Runtime;

use Infrasonic\Runtime\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Config::class)]
final class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Config::init([
            'app.name' => 'Infrasonic',
            'app.port' => 8080,
            'app.debug' => false,
        ]);
    }

    public function testTypedAccessors(): void
    {
        self::assertSame('Infrasonic', Config::string('app.name'));
        self::assertSame(8080, Config::int('app.port'));
        self::assertFalse(Config::bool('app.debug'));
    }

    public function testDefaultsForMissingKeys(): void
    {
        self::assertFalse(Config::has('app.missing'));
        self::assertSame('x', Config::string('app.missing', 'x'));
        self::assertSame(1, Config::int('app.missing', 1));
        self::assertTrue(Config::bool('app.missing', true));
    }

    public function testMissingKeyWithoutDefaultThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        Config::string('app.missing');
    }
}
