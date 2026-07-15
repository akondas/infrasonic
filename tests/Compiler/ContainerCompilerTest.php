<?php

declare(strict_types=1);

namespace Infrasonic\Tests\Compiler;

use Infrasonic\Compiler\ContainerCompiler;
use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Compiler\Metadata\Dependency;
use Infrasonic\Compiler\Metadata\ServiceDefinition;
use Infrasonic\Runtime\CompiledContainer;
use Infrasonic\Tests\Fixtures\DI\FileStore;
use Infrasonic\Tests\Fixtures\DI\Logger;
use Infrasonic\Tests\Fixtures\DI\Mailer;
use Infrasonic\Tests\Fixtures\DI\MemoryStore;
use Infrasonic\Tests\Fixtures\DI\NeedsStore;
use Infrasonic\Tests\Fixtures\DI\NodeA;
use Infrasonic\Tests\Fixtures\DI\NodeB;
use Infrasonic\Tests\Fixtures\DI\Store;
use Infrasonic\Tests\Support\GeneratedCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContainerCompiler::class)]
final class ContainerCompilerTest extends TestCase
{
    use GeneratedCode;

    /**
     * @param list<ServiceDefinition>           $services
     * @param array<class-string, class-string> $bindings
     */
    private function build(array $services, array $bindings = []): CompiledContainer
    {
        $code = (new ContainerCompiler())->compile($services, $bindings);
        $container = $this->instantiateGenerated($code, ContainerCompiler::CLASS_NAME);
        self::assertInstanceOf(CompiledContainer::class, $container);

        return $container;
    }

    public function testWiresConstructorDependencies(): void
    {
        $container = $this->build([
            new ServiceDefinition(Logger::class, [], false),
            new ServiceDefinition(Mailer::class, [new Dependency(Logger::class, 'logger')], false),
        ]);

        $mailer = $container->get(Mailer::class);
        self::assertInstanceOf(Mailer::class, $mailer);
        self::assertInstanceOf(Logger::class, $mailer->logger);
    }

    public function testServicesAreSingletons(): void
    {
        $container = $this->build([new ServiceDefinition(Logger::class, [], false)]);

        self::assertSame($container->get(Logger::class), $container->get(Logger::class));
    }

    public function testAutowiresSingleInterfaceImplementation(): void
    {
        $container = $this->build([
            new ServiceDefinition(MemoryStore::class, [], false),
            new ServiceDefinition(NeedsStore::class, [new Dependency(Store::class, 'store')], false),
        ]);

        $needsStore = $container->get(NeedsStore::class);
        self::assertInstanceOf(NeedsStore::class, $needsStore);
        self::assertInstanceOf(MemoryStore::class, $needsStore->store);
        self::assertInstanceOf(MemoryStore::class, $container->get(Store::class));
    }

    public function testExplicitBindingWins(): void
    {
        $container = $this->build(
            [
                new ServiceDefinition(MemoryStore::class, [], false),
                new ServiceDefinition(FileStore::class, [], false),
                new ServiceDefinition(NeedsStore::class, [new Dependency(Store::class, 'store')], false),
            ],
            [Store::class => FileStore::class],
        );

        $needsStore = $container->get(NeedsStore::class);
        self::assertInstanceOf(NeedsStore::class, $needsStore);
        self::assertInstanceOf(FileStore::class, $needsStore->store);
    }

    public function testAmbiguousBindingIsRejected(): void
    {
        $this->expectException(CompilerError::class);
        $this->expectExceptionMessageMatches('/Ambiguous binding/');

        (new ContainerCompiler())->compile([
            new ServiceDefinition(MemoryStore::class, [], false),
            new ServiceDefinition(FileStore::class, [], false),
            new ServiceDefinition(NeedsStore::class, [new Dependency(Store::class, 'store')], false),
        ], []);
    }

    public function testUnresolvableDependencyIsRejected(): void
    {
        $this->expectException(CompilerError::class);
        $this->expectExceptionMessageMatches('/Cannot autowire/');

        (new ContainerCompiler())->compile([
            new ServiceDefinition(NeedsStore::class, [new Dependency(Store::class, 'store')], false),
        ], []);
    }

    public function testDependencyCycleIsRejected(): void
    {
        $this->expectException(CompilerError::class);
        $this->expectExceptionMessageMatches('/Dependency cycle/');

        (new ContainerCompiler())->compile([
            new ServiceDefinition(NodeA::class, [new Dependency(NodeB::class, 'b')], false),
            new ServiceDefinition(NodeB::class, [new Dependency(NodeA::class, 'a')], false),
        ], []);
    }

    public function testHasReflectsRegistration(): void
    {
        $container = $this->build([new ServiceDefinition(Logger::class, [], false)]);

        self::assertTrue($container->has(Logger::class));
        self::assertFalse($container->has(Mailer::class));
    }
}
