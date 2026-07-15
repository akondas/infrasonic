<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Runtime;

use Infrasonic\Runtime\Cast;
use Infrasonic\Runtime\Exception\BadRouteParameter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Cast::class)]
final class CastTest extends TestCase
{
    public function testCastsScalars(): void
    {
        self::assertSame('abc', Cast::scalar('abc', 'string', 'p'));
        self::assertSame(42, Cast::scalar('42', 'int', 'p'));
        self::assertSame(-7, Cast::scalar('-7', 'int', 'p'));
        self::assertSame(3.5, Cast::scalar('3.5', 'float', 'p'));
        self::assertTrue(Cast::scalar('true', 'bool', 'p'));
        self::assertFalse(Cast::scalar('0', 'bool', 'p'));
    }

    public function testInvalidIntThrows(): void
    {
        $this->expectException(BadRouteParameter::class);
        Cast::scalar('12x', 'int', 'id');
    }

    public function testInvalidBoolThrows(): void
    {
        $this->expectException(BadRouteParameter::class);
        Cast::scalar('maybe', 'bool', 'flag');
    }
}
