<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Runtime;

use Infrasonic\Http\Attribute\Service;
use Infrasonic\Http\Handler;
use Infrasonic\Http\Middleware;
use Infrasonic\Http\Request;
use Infrasonic\Http\Response;
use Infrasonic\Runtime\CompiledRouter;
use Infrasonic\Runtime\Kernel;
use Infrasonic\Tests\Fixtures\KernelController;
use Infrasonic\Tests\Support\ArrayContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Kernel::class)]
final class KernelTest extends TestCase
{
    private function router(): CompiledRouter
    {
        return new class extends CompiledRouter {
            protected function staticRoutes(): array
            {
                return [
                    'GET' => [
                        '/ping' => ['controller' => KernelController::class, 'action' => 'ping', 'args' => []],
                        '/me' => ['controller' => KernelController::class, 'action' => 'whoami', 'args' => [['source' => 'request']]],
                        '/broken' => ['controller' => KernelController::class, 'action' => 'broken', 'args' => []],
                    ],
                ];
            }

            protected function dynamicRoutes(): array
            {
                return [
                    'GET' => [
                        [
                            'regex' => '#^/users/(?P<id>[^/]+)$#',
                            'controller' => KernelController::class,
                            'action' => 'user',
                            'args' => [['source' => 'route', 'name' => 'id', 'cast' => 'int']],
                        ],
                    ],
                ];
            }
        };
    }

    /**
     * @param list<class-string<Middleware>> $middleware
     * @param array<class-string, object>    $extraServices
     */
    private function kernel(array $middleware = [], array $extraServices = [], bool $debug = false): Kernel
    {
        $container = new ArrayContainer([KernelController::class => new KernelController()] + $extraServices);

        return new Kernel($this->router(), $container, $middleware, $debug);
    }

    public function testDispatchesStaticRoute(): void
    {
        $response = $this->kernel()->handle(new Request('GET', '/ping'));

        self::assertSame(200, $response->status);
        self::assertSame('pong', $response->body);
    }

    public function testDispatchesDynamicRouteWithCastParameter(): void
    {
        $response = $this->kernel()->handle(new Request('GET', '/users/7'));

        self::assertSame('{"id":7}', $response->body);
    }

    public function testInjectsRequest(): void
    {
        $response = $this->kernel()->handle(new Request('GET', '/me'));

        self::assertSame('{"method":"GET","path":"/me"}', $response->body);
    }

    public function testUnknownPathIsNotFound(): void
    {
        $response = $this->kernel()->handle(new Request('GET', '/nope'));

        self::assertSame(404, $response->status);
    }

    public function testWrongMethodIsMethodNotAllowed(): void
    {
        $response = $this->kernel()->handle(new Request('POST', '/ping'));

        self::assertSame(405, $response->status);
    }

    public function testInvalidParameterIsBadRequest(): void
    {
        $response = $this->kernel()->handle(new Request('GET', '/users/abc'));

        self::assertSame(400, $response->status);
    }

    public function testActionReturningNonResponseIsServerError(): void
    {
        $response = $this->kernel(debug: true)->handle(new Request('GET', '/broken'));

        self::assertSame(500, $response->status);
        self::assertStringContainsString('must return', $response->body);
    }

    public function testServerErrorHidesDetailsWithoutDebug(): void
    {
        $response = $this->kernel()->handle(new Request('GET', '/broken'));

        self::assertSame(500, $response->status);
        self::assertSame('{"status":500,"error":"Internal Server Error"}', $response->body);
    }

    public function testMiddlewareIsApplied(): void
    {
        $stamp = new #[Service] class implements Middleware {
            public function process(Request $request, Handler $next): Response
            {
                return $next->handle($request)->withHeader('X-Stamped', 'yes');
            }
        };

        $kernel = $this->kernel(
            middleware: [$stamp::class],
            extraServices: [$stamp::class => $stamp],
        );

        $response = $kernel->handle(new Request('GET', '/ping'));

        self::assertSame('yes', $response->headers['X-Stamped']);
    }
}
