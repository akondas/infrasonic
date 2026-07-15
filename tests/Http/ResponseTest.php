<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Http;

use Infrasonic\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public function testJsonEncodesBodyAndSetsContentType(): void
    {
        $response = Response::json(['message' => 'hi'], 201);

        self::assertSame(201, $response->status);
        self::assertSame('{"message":"hi"}', $response->body);
        self::assertSame('application/json; charset=utf-8', $response->headers['Content-Type']);
    }

    public function testTextAndHtmlFactories(): void
    {
        self::assertSame('text/plain; charset=utf-8', Response::text('x')->headers['Content-Type']);
        self::assertSame('text/html; charset=utf-8', Response::html('<b>x</b>')->headers['Content-Type']);
    }

    public function testNoContentHasEmptyBody(): void
    {
        $response = Response::noContent();

        self::assertSame(204, $response->status);
        self::assertSame('', $response->body);
    }

    public function testWithHeaderIsImmutable(): void
    {
        $original = Response::text('x');
        $modified = $original->withHeader('X-Test', 'yes');

        self::assertArrayNotHasKey('X-Test', $original->headers);
        self::assertSame('yes', $modified->headers['X-Test']);
    }

    public function testWithStatusIsImmutable(): void
    {
        $original = Response::text('x', 200);
        $modified = $original->withStatus(418);

        self::assertSame(200, $original->status);
        self::assertSame(418, $modified->status);
    }
}
