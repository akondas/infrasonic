<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Compiler;

use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Compiler\Metadata\RouteArgument;
use Infrasonic\Compiler\Metadata\RouteDefinition;
use Infrasonic\Compiler\RouteCompiler;
use Infrasonic\Runtime\CompiledRouter;
use Infrasonic\Tests\Fixtures\KernelController;
use Infrasonic\Tests\Support\GeneratedCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RouteCompiler::class)]
final class RouteCompilerTest extends TestCase
{
    use GeneratedCode;

    /**
     * @param list<RouteDefinition> $routes
     */
    private function build(array $routes): CompiledRouter
    {
        $code = (new RouteCompiler())->compile($routes);
        $router = $this->instantiateGenerated($code, RouteCompiler::CLASS_NAME);
        self::assertInstanceOf(CompiledRouter::class, $router);

        return $router;
    }

    public function testCompilesStaticAndDynamicRoutes(): void
    {
        $router = $this->build([
            new RouteDefinition(KernelController::class, 'ping', 'GET', '/ping', []),
            new RouteDefinition(KernelController::class, 'user', 'GET', '/users/{id}', [RouteArgument::routeParam('id', 'int')]),
        ]);

        $static = $router->match('GET', '/ping');
        self::assertNotNull($static);
        self::assertSame('ping', $static->action);

        $dynamic = $router->match('GET', '/users/42');
        self::assertNotNull($dynamic);
        self::assertSame('user', $dynamic->action);
        self::assertSame(['id' => '42'], $dynamic->params);
        self::assertSame([['source' => 'route', 'name' => 'id', 'cast' => 'int']], $dynamic->args);
    }

    public function testDynamicSegmentDoesNotCrossSlash(): void
    {
        $router = $this->build([
            new RouteDefinition(KernelController::class, 'user', 'GET', '/users/{id}', [RouteArgument::routeParam('id', 'int')]),
        ]);

        self::assertNull($router->match('GET', '/users/42/extra'));
    }

    public function testDuplicateRouteIsRejected(): void
    {
        $this->expectException(CompilerError::class);
        $this->expectExceptionMessageMatches('/Duplicate route/');

        (new RouteCompiler())->compile([
            new RouteDefinition(KernelController::class, 'ping', 'GET', '/ping', []),
            new RouteDefinition(KernelController::class, 'user', 'GET', '/ping', []),
        ]);
    }

    public function testEmptyRoutesProduceUsableRouter(): void
    {
        $router = $this->build([]);

        self::assertNull($router->match('GET', '/anything'));
    }
}
