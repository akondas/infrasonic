<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Runtime;

use Infrasonic\Runtime\CompiledRouter;
use Infrasonic\Tests\Fixtures\KernelController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CompiledRouter::class)]
final class CompiledRouterTest extends TestCase
{
    private function router(): CompiledRouter
    {
        return new class extends CompiledRouter {
            protected function staticRoutes(): array
            {
                return [
                    'GET' => [
                        '/ping' => ['controller' => KernelController::class, 'action' => 'ping', 'args' => []],
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

    public function testMatchesStaticRoute(): void
    {
        $match = $this->router()->match('GET', '/ping');

        self::assertNotNull($match);
        self::assertSame(KernelController::class, $match->controller);
        self::assertSame('ping', $match->action);
        self::assertSame([], $match->params);
    }

    public function testMatchesDynamicRouteAndCapturesParams(): void
    {
        $match = $this->router()->match('GET', '/users/99');

        self::assertNotNull($match);
        self::assertSame('user', $match->action);
        self::assertSame(['id' => '99'], $match->params);
    }

    public function testReturnsNullForUnknownPath(): void
    {
        self::assertNull($this->router()->match('GET', '/nope'));
    }

    public function testReturnsNullForWrongMethodButPathExists(): void
    {
        $router = $this->router();

        self::assertNull($router->match('POST', '/ping'));
        self::assertTrue($router->pathExists('/ping'));
        self::assertTrue($router->pathExists('/users/5'));
        self::assertFalse($router->pathExists('/nope'));
    }
}
