<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Http;

use Infrasonic\Http\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    public function testHeaderLookupIsCaseInsensitive(): void
    {
        $request = new Request('GET', '/', headers: ['content-type' => 'application/json']);

        self::assertSame('application/json', $request->header('Content-Type'));
        self::assertNull($request->header('X-Missing'));
        self::assertSame('fallback', $request->header('X-Missing', 'fallback'));
    }

    public function testWithAttributeIsImmutable(): void
    {
        $request = new Request('GET', '/');
        $withUser = $request->withAttribute('user', 'ada');

        self::assertNull($request->attribute('user'));
        self::assertSame('ada', $withUser->attribute('user'));
        self::assertSame('default', $request->attribute('missing', 'default'));
    }
}
