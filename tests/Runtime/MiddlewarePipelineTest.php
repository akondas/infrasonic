<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Runtime;

use Infrasonic\Http\Handler;
use Infrasonic\Http\Middleware;
use Infrasonic\Http\Request;
use Infrasonic\Http\Response;
use Infrasonic\Runtime\MiddlewarePipeline;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MiddlewarePipeline::class)]
final class MiddlewarePipelineTest extends TestCase
{
    public function testMiddlewareRunInOrderAndCanDecorateResponse(): void
    {
        $tag = static fn (string $label): Middleware => new class($label) implements Middleware {
            public function __construct(private readonly string $label)
            {
            }

            public function process(Request $request, Handler $next): Response
            {
                $response = $next->handle($request->withAttribute('trail', ($request->attribute('trail') ?? '').'>'.$this->label));

                return $response->withHeader('X-Trail', $response->headers['X-Trail'] ?? '');
            }
        };

        $core = new class implements Handler {
            public function handle(Request $request): Response
            {
                return Response::text('ok')->withHeader('X-Trail', (string) $request->attribute('trail'));
            }
        };

        $pipeline = new MiddlewarePipeline([$tag('a'), $tag('b')], $core);
        $response = $pipeline->handle(new Request('GET', '/'));

        self::assertSame('>a>b', $response->headers['X-Trail']);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $blocker = new class implements Middleware {
            public function process(Request $request, Handler $next): Response
            {
                return Response::text('blocked', 403);
            }
        };

        $core = new class implements Handler {
            public function handle(Request $request): Response
            {
                return Response::text('should not run');
            }
        };

        $response = (new MiddlewarePipeline([$blocker], $core))->handle(new Request('GET', '/'));

        self::assertSame(403, $response->status);
        self::assertSame('blocked', $response->body);
    }

    public function testEmptyPipelineCallsCore(): void
    {
        $core = new class implements Handler {
            public function handle(Request $request): Response
            {
                return Response::text('core');
            }
        };

        self::assertSame('core', (new MiddlewarePipeline([], $core))->handle(new Request('GET', '/'))->body);
    }
}
