<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Compiler;

use App\Clock\SystemClock;
use App\Controller\GreetingController;
use App\Controller\HomeController;
use App\Middleware\RequestTimer;
use App\Service\GreetingService;
use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Compiler\SourceScanner;
use Infrasonic\Tests\Fixtures\Scan\BadArgController;
use Infrasonic\Tests\Fixtures\Scan\BuiltinDepService;
use Infrasonic\Tests\Fixtures\Scan\CatalogController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceScanner::class)]
final class SourceScannerTest extends TestCase
{
    public function testDiscoversServicesControllersAndRoutes(): void
    {
        $result = (new SourceScanner())->scan([
            SystemClock::class,
            GreetingService::class,
            HomeController::class,
            GreetingController::class,
            RequestTimer::class,
        ]);

        self::assertCount(5, $result->services);
        self::assertCount(2, array_filter($result->services, static fn ($s) => $s->isController));
        self::assertCount(4, $result->routes);
    }

    public function testResolvesConstructorDependencies(): void
    {
        $result = (new SourceScanner())->scan([GreetingService::class]);

        $service = $result->services[0];
        self::assertSame(GreetingService::class, $service->id);
        self::assertCount(1, $service->dependencies);
        self::assertSame('App\Clock\Clock', $service->dependencies[0]->type);
    }

    public function testCompilesRouteArgumentsFromPathAndSignature(): void
    {
        $result = (new SourceScanner())->scan([CatalogController::class]);

        $byAction = [];
        foreach ($result->routes as $route) {
            $byAction[$route->action] = $route;
        }

        self::assertSame(
            [
                ['source' => 'route', 'name' => 'sku', 'cast' => 'string'],
                ['source' => 'route', 'name' => 'qty', 'cast' => 'int'],
            ],
            array_map(static fn ($a) => $a->toArray(), $byAction['stock']->arguments),
        );

        self::assertSame([['source' => 'request']], array_map(static fn ($a) => $a->toArray(), $byAction['create']->arguments));
    }

    public function testRejectsUnsupportedConstructorDependency(): void
    {
        $this->expectException(CompilerError::class);
        $this->expectExceptionMessageMatches('/built-in type string/');

        (new SourceScanner())->scan([BuiltinDepService::class]);
    }

    public function testRejectsRouteArgumentWithoutMatchingPathParameter(): void
    {
        $this->expectException(CompilerError::class);
        $this->expectExceptionMessageMatches('/does not match a path parameter/');

        (new SourceScanner())->scan([BadArgController::class]);
    }
}
