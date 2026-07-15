<?php

declare(strict_types=1);

namespace Infrasonic\Compiler;

use Infrasonic\Compiler\Exception\CompilerError;
use Infrasonic\Compiler\Metadata\Dependency;
use Infrasonic\Compiler\Metadata\RouteArgument;
use Infrasonic\Compiler\Metadata\RouteDefinition;
use Infrasonic\Compiler\Metadata\ScanResult;
use Infrasonic\Compiler\Metadata\ServiceDefinition;
use Infrasonic\Http\Attribute\Route;
use Infrasonic\Http\Attribute\Service;
use Infrasonic\Http\Request;

/**
 * Reads PHP 8 attributes via reflection and turns them into framework metadata.
 *
 * Build-time only. This is the single place reflection is allowed; the produced
 * metadata is compiled into plain PHP so no reflection happens at runtime.
 */
final class SourceScanner
{
    private const array SCALAR_CASTS = ['int', 'float', 'bool', 'string'];

    /**
     * @param list<class-string> $classes
     */
    public function scan(array $classes): ScanResult
    {
        $services = [];
        $routes = [];

        foreach ($classes as $class) {
            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            $routeMethods = $this->routeMethods($reflection);
            $isController = [] !== $routeMethods;
            $isService = $isController || [] !== $reflection->getAttributes(Service::class);

            if (!$isService) {
                continue;
            }

            $services[] = new ServiceDefinition(
                id: $class,
                dependencies: $this->dependencies($reflection),
                isController: $isController,
            );

            foreach ($routeMethods as [$method, $route]) {
                $routes[] = $this->route($reflection, $method, $route);
            }
        }

        return new ScanResult($services, $routes);
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<array{\ReflectionMethod, Route}>
     */
    private function routeMethods(\ReflectionClass $reflection): array
    {
        $found = [];
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic()) {
                continue;
            }
            foreach ($method->getAttributes(Route::class) as $attribute) {
                $found[] = [$method, $attribute->newInstance()];
            }
        }

        return $found;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     *
     * @return list<Dependency>
     */
    private function dependencies(\ReflectionClass $reflection): array
    {
        $constructor = $reflection->getConstructor();
        if (null === $constructor) {
            return [];
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                throw CompilerError::unsupportedDependency($reflection->getName(), $parameter->getName(), $type instanceof \ReflectionNamedType ? 'built-in type '.$type->getName() : 'no type declared');
            }

            /** @var class-string $typeName */
            $typeName = $type->getName();
            $dependencies[] = new Dependency($typeName, $parameter->getName());
        }

        return $dependencies;
    }

    /**
     * @param \ReflectionClass<object> $reflection
     */
    private function route(\ReflectionClass $reflection, \ReflectionMethod $method, Route $route): RouteDefinition
    {
        /** @var class-string $controller */
        $controller = $reflection->getName();
        $placeholders = $this->placeholders($route->path);
        $consumed = [];
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if ($type instanceof \ReflectionNamedType && Request::class === $type->getName()) {
                $arguments[] = RouteArgument::request();

                continue;
            }

            if (!\in_array($name, $placeholders, true)) {
                throw CompilerError::invalidRouteArgument($controller, $method->getName(), $name, \sprintf('does not match a path parameter "{%s}" and is not the %s type', $name, Request::class));
            }

            $arguments[] = RouteArgument::routeParam($name, $this->cast($controller, $method->getName(), $parameter));
            $consumed[] = $name;
        }

        foreach ($placeholders as $placeholder) {
            if (!\in_array($placeholder, $consumed, true)) {
                throw CompilerError::missingRouteParameter($controller, $method->getName(), $placeholder);
            }
        }

        return new RouteDefinition(
            controller: $controller,
            action: $method->getName(),
            httpMethod: $route->method->value,
            path: $route->path,
            arguments: $arguments,
        );
    }

    private function cast(string $controller, string $action, \ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        if (!$type instanceof \ReflectionNamedType) {
            return 'string';
        }

        if (!$type->isBuiltin() || !\in_array($type->getName(), self::SCALAR_CASTS, true)) {
            throw CompilerError::invalidRouteArgument($controller, $action, $parameter->getName(), \sprintf('has unsupported type "%s"; route parameters must be string, int, float or bool', $type->getName()));
        }

        return $type->getName();
    }

    /**
     * @return list<string>
     */
    private function placeholders(string $path): array
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);

        return $matches[1];
    }
}
