<?php

declare(strict_types=1);

namespace Infrasonic\Compiler;

use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Compiler\Metadata\ServiceDefinition;

/**
 * Generates the compiled container source.
 *
 * Every service becomes a private factory method that instantiates the class
 * with its dependencies resolved by direct method calls — no reflection, no
 * definition graph. Interfaces are resolved to a concrete implementation via
 * explicit bindings or, when unambiguous, a single discovered implementation.
 * Dependency cycles are rejected here so they can never recurse at runtime.
 */
final class ContainerCompiler
{
    public const string NAMESPACE = 'Infrasonic\\Generated';
    public const string CLASS_NAME = 'CompiledContainer';
    public const string FQCN = self::NAMESPACE.'\\'.self::CLASS_NAME;

    /** @var array<class-string, ServiceDefinition> */
    private array $services = [];

    /** @var array<class-string, class-string> concrete id resolved for each type (concrete + alias) */
    private array $resolved = [];

    /** @var array<class-string, string> concrete id => factory method name */
    private array $factoryNames = [];

    /**
     * @param list<ServiceDefinition>           $services
     * @param array<class-string, class-string> $bindings interface => concrete
     */
    public function compile(array $services, array $bindings): string
    {
        $this->services = [];
        $this->resolved = [];
        $this->factoryNames = [];

        foreach ($services as $service) {
            $this->services[$service->id] = $service;
            $this->factoryNames[$service->id] = $this->makeFactoryName($service->id);
        }

        // Resolve every dependency to a concrete service and detect cycles.
        foreach ($this->services as $service) {
            foreach ($service->dependencies as $dependency) {
                $this->resolve($dependency->type, $service->id, $dependency->parameterName, $bindings);
            }
        }
        $this->detectCycles();

        // Record explicit bindings as aliases even if never injected, so get() works for them.
        foreach ($bindings as $interface => $concrete) {
            if (!isset($this->services[$concrete])) {
                throw CompilerError::unresolvableDependency($interface, '(binding)', $concrete);
            }
            $this->resolved[$interface] = $concrete;
        }

        return $this->render();
    }

    /**
     * @param class-string                      $type
     * @param class-string                      $service
     * @param array<class-string, class-string> $bindings
     *
     * @return class-string concrete service id
     */
    private function resolve(string $type, string $service, string $parameter, array $bindings): string
    {
        if (isset($this->resolved[$type])) {
            return $this->resolved[$type];
        }

        // A concrete service that exists is its own implementation.
        if (isset($this->services[$type])) {
            return $this->resolved[$type] = $type;
        }

        if (isset($bindings[$type])) {
            $concrete = $bindings[$type];
            if (!isset($this->services[$concrete])) {
                throw CompilerError::unresolvableDependency($service, $parameter, $type);
            }

            return $this->resolved[$type] = $concrete;
        }

        $implementations = $this->implementationsOf($type);
        if (1 === \count($implementations)) {
            return $this->resolved[$type] = $implementations[0];
        }
        if (\count($implementations) > 1) {
            throw CompilerError::ambiguousBinding($type, $service, $parameter);
        }

        throw CompilerError::unresolvableDependency($service, $parameter, $type);
    }

    /**
     * @param class-string $type
     *
     * @return list<class-string>
     */
    private function implementationsOf(string $type): array
    {
        if (!interface_exists($type) && !class_exists($type)) {
            return [];
        }

        $implementations = [];
        foreach ($this->services as $id => $_) {
            if ($id !== $type && is_a($id, $type, true)) {
                $implementations[] = $id;
            }
        }

        return $implementations;
    }

    private function detectCycles(): void
    {
        /** @var array<class-string, int> $state 0=unseen,1=visiting,2=done */
        $state = [];

        foreach (array_keys($this->services) as $id) {
            $this->visit($id, $state, []);
        }
    }

    /**
     * @param class-string             $id
     * @param array<class-string, int> $state
     * @param list<class-string>       $stack
     */
    private function visit(string $id, array &$state, array $stack): void
    {
        $current = $state[$id] ?? 0;
        if (2 === $current) {
            return;
        }
        if (1 === $current) {
            $stack[] = $id;
            throw CompilerError::dependencyCycle(implode(' -> ', $stack));
        }

        $state[$id] = 1;
        $stack[] = $id;

        foreach ($this->services[$id]->dependencies as $dependency) {
            $concrete = $this->resolved[$dependency->type];
            $this->visit($concrete, $state, $stack);
        }

        $state[$id] = 2;
    }

    private function render(): string
    {
        $arms = [];
        $hasIds = [];

        foreach ($this->services as $id => $_) {
            $arms[] = \sprintf('            \\%s::class => $this->%s(),', $id, $this->factoryNames[$id]);
            $hasIds[] = '\\'.$id.'::class';
        }
        foreach ($this->resolved as $type => $concrete) {
            if (isset($this->services[$type])) {
                continue; // concrete already emitted above
            }
            $arms[] = \sprintf('            \\%s::class => $this->%s(),', $type, $this->factoryNames[$concrete]);
            $hasIds[] = '\\'.$type.'::class';
        }

        $factories = [];
        foreach ($this->services as $id => $service) {
            $factories[] = $this->renderFactory($service);
        }

        $armsCode = implode("\n", $arms);
        $hasCode = [] === $hasIds ? '' : implode(",\n                ", $hasIds).' => true,';
        $factoriesCode = implode("\n\n", $factories);

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$this->classNamespace()};

            /**
             * Compiled by Infrasonic. Do not edit.
             */
            final class {$this->className()} extends \\Infrasonic\\Runtime\\CompiledContainer
            {
                public function get(string \$id): object
                {
                    return match (\$id) {
            {$armsCode}
                        default => \$this->fail(\$id),
                    };
                }

                public function has(string \$id): bool
                {
                    return match (\$id) {
                        {$hasCode}
                        default => false,
                    };
                }

            {$factoriesCode}
            }

            PHP;
    }

    private function renderFactory(ServiceDefinition $service): string
    {
        $args = [];
        foreach ($service->dependencies as $dependency) {
            $concrete = $this->resolved[$dependency->type];
            $args[] = '$this->'.$this->factoryNames[$concrete].'()';
        }

        $arguments = implode(', ', $args);
        $name = $this->factoryNames[$service->id];

        return <<<PHP
                private function {$name}(): \\{$service->id}
                {
                    return \$this->singletons[\\{$service->id}::class] ??= new \\{$service->id}({$arguments});
                }
            PHP;
    }

    /**
     * @param class-string $id
     */
    private function makeFactoryName(string $id): string
    {
        $base = 'create_'.str_replace('\\', '_', $id);

        $name = $base;
        $suffix = 1;
        while (\in_array($name, $this->factoryNames, true)) {
            $name = $base.'_'.$suffix;
            ++$suffix;
        }

        return $name;
    }

    private function classNamespace(): string
    {
        return self::NAMESPACE;
    }

    private function className(): string
    {
        return self::CLASS_NAME;
    }
}
